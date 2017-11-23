<?php

namespace kuiper\annotations;

use kuiper\annotations\exception\ClassNotFoundException;
use kuiper\reflection\exception\ReflectionException;
use kuiper\reflection\FqcnResolver;
use kuiper\reflection\ReflectionFileFactory;
use kuiper\reflection\ReflectionFileFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\ClassType;
use kuiper\reflection\type\CompositeType;
use kuiper\reflection\TypeUtils;

class DocReader implements DocReaderInterface
{
    const METHOD_PARAM_REGEX = '/@param\s+(\S+)\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';

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
        DocUtils::checkDocReadability();

        $this->reflectionFileFactory = $reflectionFileFactory ?: ReflectionFileFactory::createInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyType(\ReflectionProperty $property)
    {
        return $this->getCached($property, function () use ($property) {
            return $this->parseTypeFromDocBlock($property->getDocComment(), $property->getDeclaringClass(), 'var');
        }, 'property:');
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyClass(\ReflectionProperty $property)
    {
        return $this->getClassType($this->getPropertyType($property));
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypes(\ReflectionMethod $method)
    {
        return $this->getCached($method, function () use ($method) {
            $parameters = [];
            foreach ($method->getParameters() as $parameter) {
                if (method_exists($parameter, 'hasType')
                    && $parameter->hasType()) {
                    // detected from php 7.0 ReflectionType
                    $parameters[$parameter->getName()] = ReflectionType::forName($parameter->getType());
                } else {
                    $parameters[$parameter->getName()] = ReflectionType::forName('mixed');
                }
            }
            $doc = $this->getMethodDocComment($method);
            if ($doc && preg_match_all(self::METHOD_PARAM_REGEX, $doc, $matches)) {
                $declaringClass = $method->getDeclaringClass();
                foreach ($matches[2] as $index => $name) {
                    if (!isset($parameters[$name])) {
                        continue;
                    }
                    if (TypeUtils::isUnknown($parameters[$name])) {
                        // if type is unknown, parse from doc block param tag
                        $parameters[$name] = $this->parseType($matches[1][$index], $declaringClass);
                    }
                }
            }

            return $parameters;
        }, 'method:');
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterClasses(\ReflectionMethod $method)
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
    public function getReturnType(\ReflectionMethod $method)
    {
        return $this->getCached($method, function () use ($method) {
            if (method_exists($method, 'hasReturnType')
                && $method->hasReturnType()) {
                // detected from php 7.0 ReflectionType
                $type = ReflectionType::forName($method->getReturnType());
                if (!TypeUtils::isUnknown($type)) {
                    return $type;
                }
            }
            $doc = $this->getMethodDocComment($method);
            if ($doc && preg_match(self::getDocTagRegexp('return'), $doc)) {
                return $this->parseTypeFromDocBlock($doc, $method->getDeclaringClass(), 'return');
            } else {
                return isset($type) ? $type : ReflectionType::forName('mixed');
            }
        }, 'return:');
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnClass(\ReflectionMethod $method)
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

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return null|string
     */
    private function getClassType(ReflectionTypeInterface $type)
    {
        return TypeUtils::isClass($type) ? $type->getName() : null;
    }

    /**
     * @param string|false     $docBlock
     * @param \ReflectionClass $declaringClass
     * @param string           $annotationTag
     *
     * @return ReflectionTypeInterface
     */
    private function parseTypeFromDocBlock($docBlock, \ReflectionClass $declaringClass, $annotationTag)
    {
        if (!$docBlock || !preg_match(self::getDocTagRegexp($annotationTag), $docBlock, $matches)) {
            return ReflectionType::forName('mixed');
        }

        return $this->parseType($matches[1], $declaringClass);
    }

    /**
     * @param string $annotationTag
     *
     * @return string
     */
    private static function getDocTagRegexp($annotationTag)
    {
        return '/@'.$annotationTag.'\s+(\S+)/';
    }

    /**
     * Parses the type.
     *
     * @param string           $type
     * @param \ReflectionClass $declaringClass
     *
     * @return ReflectionTypeInterface
     *
     * @throws ClassNotFoundException
     */
    private function parseType(string $type, \ReflectionClass $declaringClass)
    {
        if (empty($type)) {
            throw new \InvalidArgumentException('Type cannot be empty');
        }
        if (in_array($type, ['self', 'static', '$this'])) {
            return ReflectionType::forName($declaringClass->getName());
        }
        $reflectionType = TypeUtils::parse($type);
        try {
            return $this->resolveFqcn($reflectionType, $declaringClass);
        } catch (ReflectionException $e) {
            trigger_error('Parse type error: '.$e->getMessage());

            return ReflectionType::forName('mixed');
        }
    }

    private function resolveFqcn(ReflectionTypeInterface $type, \ReflectionClass $declaringClass)
    {
        /** @var ArrayType $type */
        if (TypeUtils::isArray($type) && TypeUtils::isClass($type->getValueType())) {
            $valueType = $this->resolveFqcn($type->getValueType(), $declaringClass);

            return new ArrayType($valueType, $type->getDimension());
        } elseif (TypeUtils::isComposite($type)) {
            $types = [];
            $hasClassType = false;
            /** @var CompositeType $type */
            foreach ($type->getTypes() as $subType) {
                if (TypeUtils::isClass($subType)) {
                    $hasClassType = true;
                    $types[] = $this->resolveFqcn($subType, $declaringClass);
                } else {
                    $types[] = $subType;
                }
            }
            if ($hasClassType) {
                return new CompositeType($types);
            }
        } elseif (TypeUtils::isClass($type)) {
            $name = $type->getName();
            if ($name[0] !== '\\' && $declaringClass->getFileName()) {
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

    private function getMethodDocComment(\ReflectionMethod $method)
    {
        $doc = $method->getDocComment();
        if ($doc && stripos($doc, '@inheritdoc') !== false) {
            $name = $method->getName();
            $class = $method->getDeclaringClass();
            if (false !== ($parent = $class->getParentClass())) {
                if ($parent->hasMethod($name)) {
                    return $this->getMethodDocComment($parent->getMethod($name));
                }
            }
            foreach ($class->getInterfaces() as $interface) {
                if ($interface->hasMethod($name)) {
                    return $interface->getMethod($name)->getDocComment();
                }
            }
        }

        return $doc;
    }

    /**
     * @param string $fileName
     *
     * @return FqcnResolver
     */
    private function createFqcnResolver(string $fileName): FqcnResolver
    {
        $reflectionFile = $this->reflectionFileFactory->create($fileName);

        return new FqcnResolver($reflectionFile);
    }

    private function assertClassExists(string $className)
    {
        if (class_exists($className) || interface_exists($className)) {
            return;
        }
        throw new ClassNotFoundException("Class '{$className}' does not exist");
    }
}
