<?php

declare(strict_types=1);

namespace kuiper\reflection;

use kuiper\reflection\exception\ClassNotFoundException;
use kuiper\reflection\exception\ReflectionException;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\ClassType;
use kuiper\reflection\type\CompositeType;

class ReflectionDocBlockFactory implements ReflectionDocBlockFactoryInterface
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

    /**
     * @var ReflectionDocBlockFactoryInterface
     */
    private static $INSTANCE;

    private function __construct(?ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        $this->reflectionFileFactory = $reflectionFileFactory;
    }

    public static function createInstance(ReflectionFileFactoryInterface $reflectionFileFactory): ReflectionDocBlockFactoryInterface
    {
        self::$INSTANCE = new self($reflectionFileFactory);

        return self::$INSTANCE;
    }

    public static function getInstance(): ReflectionDocBlockFactoryInterface
    {
        return self::$INSTANCE ?? self::createInstance(ReflectionFileFactory::getInstance());
    }

    public function createPropertyDocBlock(\ReflectionProperty $property): ReflectionPropertyDocBlockInterface
    {
        return $this->getCached($property, function () use ($property): ReflectionPropertyDocBlock {
            $type = $this->parseTypeFromDocBlock($property->getDocComment(), $this->getPropertyDeclaringClass($property), 'var');

            return new ReflectionPropertyDocBlock($property, $type);
        }, 'property:');
    }

    public function createMethodDocBlock(\ReflectionMethod $method): ReflectionMethodDocBlockInterface
    {
        return $this->getCached($method, function () use ($method): ReflectionMethodDocBlock {
            return new ReflectionMethodDocBlock($method, $this->getParameterTypes($method), $this->getReturnType($method));
        }, 'method:');
    }

    private function getParameterTypes(\ReflectionMethod $method): array
    {
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
    }

    private function getReturnType(\ReflectionMethod $method): ReflectionTypeInterface
    {
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
        }

        return $type ?? ReflectionType::forName('mixed');
    }

    /**
     * @param \ReflectionMethod|\ReflectionProperty $reflection
     *
     * @return mixed
     */
    private function getCached($reflection, callable $callback, string $prefix)
    {
        $className = $reflection->getDeclaringClass()->getName();
        $key = $prefix.$reflection->getName();
        if (!isset(self::$CACHE[$className][$key])) {
            self::$CACHE[$className][$key] = $callback();
        }

        return self::$CACHE[$className][$key];
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

    private static function getDocTagRegexp(string $annotationTag): string
    {
        return '/@'.$annotationTag.'\s+(\S+)/';
    }

    /**
     * Parses the type.
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

    /**
     * @throws exception\SyntaxErrorException
     * @throws ClassNotFoundException
     * @throws exception\FileNotFoundException
     */
    private function resolveFqcn(ReflectionTypeInterface $type, \ReflectionClass $declaringClass): ReflectionTypeInterface
    {
        /** @var ArrayType|CompositeType $type */
        if ($type->isArray() && $type->getValueType()->isClass()) {
            $valueType = $this->resolveFqcn($type->getValueType(), $declaringClass);

            return new ArrayType($valueType, $type->getDimension());
        }

        if ($type->isComposite()) {
            $types = [];
            foreach ($type->getTypes() as $subType) {
                $types[] = $this->resolveFqcn($subType, $declaringClass);
            }

            return new CompositeType($types);
        }

        if ($type->isClass()) {
            $name = $type->getName();
            if ('\\' !== $name[0] && $declaringClass->getFileName()) {
                $fqcn = $this->createFqcnResolver($declaringClass->getFileName())
                    ->resolve($name, $declaringClass->getNamespaceName());
                $this->assertClassExists($fqcn);

                return new ClassType($fqcn);
            }

            $this->assertClassExists($name);
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

    /**
     * @throws ClassNotFoundException
     */
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
