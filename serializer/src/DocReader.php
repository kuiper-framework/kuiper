<?php

declare(strict_types=1);

namespace kuiper\serializer;

use kuiper\reflection\exception\ReflectionException;
use kuiper\reflection\FqcnResolver;
use kuiper\reflection\ReflectionFileFactory;
use kuiper\reflection\ReflectionFileFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\ClassType;
use kuiper\reflection\type\CompositeType;
use kuiper\serializer\exception\ClassNotFoundException;

class DocReader implements DocReaderInterface
{
    private const METHOD_PARAM_REGEX = '/@param\s+(\S+)\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';

    /**
     * @var ReflectionFileFactoryInterface
     */
    private $reflectionFileFactory;

    /**
     * @var array
     */
    private static $CACHE;

    public function __construct(ReflectionFileFactoryInterface $reflectionFileFactory = null)
    {
        $this->reflectionFileFactory = $reflectionFileFactory ?: ReflectionFileFactory::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyType(\ReflectionProperty $property): ReflectionTypeInterface
    {
        return $this->getCached($property, function () use ($property) {
            return $this->parseTypeFromDocBlock($property->getDocComment(), $this->getPropertyDeclaringClass($property), 'var');
        }, 'property:');
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyClass(\ReflectionProperty $property): ?string
    {
        return $this->getClassType($this->getPropertyType($property));
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypes(\ReflectionMethod $method): array
    {
        return $this->getCached($method, function () use ($method) {
            /** @var ReflectionTypeInterface[] $parameterTypes */
            $parameterTypes = [];
            foreach ($method->getParameters() as $parameter) {
                if (method_exists($parameter, 'hasType')
                    && $parameter->hasType()) {
                    // detected from php 7.0 ReflectionType
                    $parameterTypes[$parameter->getName()] = ReflectionType::fromPhpType($parameter->getType());
                } else {
                    $parameterTypes[$parameter->getName()] = ReflectionType::forName('mixed');
                }
            }
            $doc = $this->getMethodDocComment($method);
            if ($doc->docBlock && preg_match_all(self::METHOD_PARAM_REGEX, $doc->docBlock, $matches)) {
                $declaringClass = $doc->declaringClass;
                foreach ($matches[2] as $index => $name) {
                    if (!isset($parameterTypes[$name])) {
                        continue;
                    }
                    if ($parameterTypes[$name]->isUnknown()) {
                        // if type is unknown, parse from doc block param tag
                        $parameterTypes[$name] = $this->parseType($matches[1][$index], $declaringClass);
                    }
                }
            }

            return $parameterTypes;
        }, 'method:');
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterClasses(\ReflectionMethod $method): array
    {
        $parameters = [];
        foreach ($this->getParameterTypes($method) as $name => $type) {
            $parameters[$name] = $this->getClassType($type);
        }

        return array_filter($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnType(\ReflectionMethod $method): ReflectionTypeInterface
    {
        return $this->getCached($method, function () use ($method) {
            if (method_exists($method, 'hasReturnType')
                && $method->hasReturnType()) {
                // detected from php 7.0 ReflectionType
                $type = ReflectionType::fromPhpType($method->getReturnType());
                if (!$type->isUnknown()) {
                    return $type;
                }
            }
            $doc = $this->getMethodDocComment($method);
            if ($doc->docBlock && preg_match(self::getDocTagRegexp('return'), $doc->docBlock)) {
                return $this->parseTypeFromDocBlock($doc->docBlock, $doc->declaringClass, 'return');
            } else {
                return $type ?? ReflectionType::forName('mixed');
            }
        }, 'return:');
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnClass(\ReflectionMethod $method): ?string
    {
        return $this->getClassType($this->getReturnType($method));
    }

    /**
     * @param \ReflectionMethod|\ReflectionProperty $reflection
     * @param callable                              $callback
     * @param string                                $prefix
     *
     * @return mixed
     */
    private function getCached($reflection, $callback, $prefix = null)
    {
        $className = $reflection->getDeclaringClass()->getName();
        $key = $prefix.$reflection->getName();
        if (isset(self::$CACHE[$className][$key])) {
            return self::$CACHE[$className][$key];
        }

        return self::$CACHE[$className][$key] = $callback();
    }

    private function getClassType(ReflectionTypeInterface $type): ?string
    {
        return $type->isClass() ? $type->getName() : null;
    }

    /**
     * @param string|false $docBlock
     * @param string       $annotationTag
     */
    private function parseTypeFromDocBlock($docBlock, \ReflectionClass $declaringClass, $annotationTag): ReflectionTypeInterface
    {
        if (!$docBlock || !preg_match(self::getDocTagRegexp($annotationTag), $docBlock, $matches)) {
            return ReflectionType::forName('mixed');
        }

        return $this->parseType($matches[1], $declaringClass);
    }

    /**
     * @param string $annotationTag
     */
    private static function getDocTagRegexp($annotationTag): string
    {
        return '/@'.$annotationTag.'\s+(\S+)/';
    }

    /**
     * Parses the type.
     *
     * @throws ClassNotFoundException
     */
    private function parseType(string $type, \ReflectionClass $declaringClass): ReflectionTypeInterface
    {
        if (empty($type)) {
            throw new \InvalidArgumentException('Type cannot be empty');
        }
        if (in_array($type, ['self', 'static', '$this'], true)) {
            return ReflectionType::forName($declaringClass->getName());
        }
        $reflectionType = ReflectionType::parse($type);
        try {
            return $this->resolveFqcn($reflectionType, $declaringClass);
        } catch (ReflectionException $e) {
            trigger_error('Parse type error: '.$e->getMessage());

            return ReflectionType::forName('mixed');
        }
    }

    private function resolveFqcn(ReflectionTypeInterface $type, \ReflectionClass $declaringClass): ReflectionTypeInterface
    {
        /** @var ArrayType $type */
        if ($type->isArray() && $type->getValueType()->isClass()) {
            $valueType = $this->resolveFqcn($type->getValueType(), $declaringClass);

            return new ArrayType($valueType, $type->getDimension());
        } elseif ($type->isComposite()) {
            $types = [];
            /** @var CompositeType $type */
            foreach ($type->getTypes() as $subType) {
                $types[] = $this->resolveFqcn($subType, $declaringClass);
            }

            return new CompositeType($types);
        } elseif ($type->isClass()) {
            $name = $type->getName();
            if ('\\' !== $name[0] && $declaringClass->getFileName()) {
                $fqcn = $this->createFqcnResolver($declaringClass->getFileName())
                    ->resolve($name, $declaringClass->getNamespaceName());
                $this->assertClassExists($fqcn);

                return new ClassType($fqcn);
            } else {
                $this->assertClassExists($name);
            }
        }

        return $type;
    }

    private function getMethodDocComment(\ReflectionMethod $method): object
    {
        $doc = $method->getDocComment();
        if ($doc && false !== stripos($doc, '@inheritdoc')) {
            $name = $method->getName();
            $class = $method->getDeclaringClass();
            if (false !== ($parent = $class->getParentClass())) {
                if ($parent->hasMethod($name)) {
                    return $this->getMethodDocComment($parent->getMethod($name));
                }
            }
            foreach ($class->getInterfaces() as $interface) {
                if ($interface->hasMethod($name)) {
                    $method = $interface->getMethod($name);

                    return (object) [
                        'docBlock' => $method->getDocComment(),
                        'declaringClass' => $method->getDeclaringClass(),
                    ];
                }
            }
        }

        return (object) [
            'docBlock' => $doc,
            'declaringClass' => $method->getDeclaringClass(),
        ];
    }

    private function createFqcnResolver(string $fileName): FqcnResolver
    {
        $reflectionFile = $this->reflectionFileFactory->create($fileName);

        return new FqcnResolver($reflectionFile);
    }

    private function assertClassExists(string $className): void
    {
        if (class_exists($className) || interface_exists($className)) {
            return;
        }
        throw new ClassNotFoundException("Class '{$className}' does not exist");
    }

    protected function getPropertyDeclaringClass(\ReflectionProperty $property): \ReflectionClass
    {
        foreach ($property->getDeclaringClass()->getTraits() as $trait) {
            if ($trait->hasProperty($property->getName())) {
                return $trait;
            }
        }

        return $property->getDeclaringClass();
    }
}
