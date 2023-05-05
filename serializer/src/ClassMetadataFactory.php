<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\serializer;

use Exception;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\serializer\attribute\SerializeIgnore;
use kuiper\serializer\attribute\SerializeName;
use kuiper\serializer\exception\NotSerializableException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

class ClassMetadataFactory
{
    /**
     * @var array<string,ClassMetadata>
     */
    private array $cache = [];

    public function __construct(private readonly ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory)
    {
    }

    /**
     * gets class properties metadata.
     *
     * @throws ReflectionException
     */
    public function create(string $className): ClassMetadata
    {
        if ($this->isCacheHit($className)) {
            return $this->getCached($className);
        }
        $metadata = new ClassMetadata($className);
        $class = new ReflectionClass($className);
        $this->parseConstructor($class, $metadata);
        $this->parseMethods($class, $metadata);
        $this->parseProperties($class, $metadata);

        return $this->save($className, $metadata);
    }

    public function clearCache(string $className = null): void
    {
        if (isset($className)) {
            unset($this->cache[$className]);
        } else {
            $this->cache = [];
        }
    }

    private function parseConstructor(ReflectionClass $class, ClassMetadata $metadata): void
    {
        $reflectionMethod = $class->getConstructor();
        if (null === $reflectionMethod) {
            return;
        }
        $docBlock = $this->reflectionDocBlockFactory->createMethodDocBlock($reflectionMethod);
        $parameterTypes = $docBlock->getParameterTypes();

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $name = $parameter->getName();
            if ($this->isIgnore($parameter)) {
                continue;
            }
            $field = new Field($metadata->getClassName(), $name);
            $field->setType($parameterTypes[$name] ?? null);
            $serializeName = $this->getSerializeName($parameter);
            if (null !== $serializeName) {
                $field->setSerializeName($serializeName);
            }
            $metadata->addConstructorArg($field);
        }
    }

    private function parseMethods(ReflectionClass $class, ClassMetadata $metadata): void
    {
        $isException = $class->isSubclassOf(Exception::class);
        foreach ($class->getMethods() as $method) {
            if ($method->isStatic() || !$method->isPublic()) {
                continue;
            }
            // ignore trace and traceAsString for Exception class
            if ($isException && in_array($method->getName(), ['getTrace', 'getTraceAsString'], true)) {
                continue;
            }
            $this->parseSetter($method, $metadata);
            $this->parseGetter($method, $metadata);
        }
    }

    protected function parseSetter(ReflectionMethod $method, ClassMetadata $metadata): void
    {
        $name = $method->getName();
        if (str_starts_with($name, 'set')
            && 1 === $method->getNumberOfParameters()
            && !$this->isIgnore($method)) {
            $docBlock = $this->reflectionDocBlockFactory->createMethodDocBlock($method);
            $types = array_values($docBlock->getParameterTypes());
            if (!$this->validateType($types[0])) {
                throw new NotSerializableException(sprintf('Cannot serialize class %s for method %s', $method->getDeclaringClass()->getName(), $method->getName()));
            }
            $field = new Field($metadata->getClassName(), lcfirst(substr($name, 3)));
            $field->setType($types[0]);
            $field->setSetter($name);
            $serializeName = $this->getSerializeName($method);
            if (null !== $serializeName) {
                $field->setSerializeName($serializeName);
            }
            $metadata->addSetter($field);
        }
    }

    protected function parseGetter(ReflectionMethod $method, ClassMetadata $metadata): void
    {
        $name = $method->getName();
        if (preg_match('/^(get|is|has)(.+)/', $name, $matches)
            && 0 === $method->getNumberOfParameters()
            && !$this->isIgnore($method)) {
            $type = $this->reflectionDocBlockFactory->createMethodDocBlock($method)->getReturnType();
            if (!$this->validateType($type)) {
                throw new NotSerializableException(sprintf('Cannot serialize class %s for method %s', $method->getDeclaringClass()->getName(), $method->getName()));
            }
            $field = new Field($metadata->getClassName(), lcfirst($matches[2]));
            $field->setType($type);
            $field->setGetter($name);
            $serializeName = $this->getSerializeName($method);
            if (null !== $serializeName) {
                $field->setSerializeName($serializeName);
            }
            $metadata->addGetter($field);
        }
    }

    private function parseProperties(ReflectionClass $class, ClassMetadata $metadata): void
    {
        foreach ($class->getProperties() as $property) {
            if ($property->isStatic() || $this->isIgnore($property)) {
                continue;
            }
            $type = $this->reflectionDocBlockFactory->createPropertyDocBlock($property)->getType();
            if (!$this->validateType($type)) {
                throw new NotSerializableException(sprintf('Cannot serialize class %s for property %s', $property->getDeclaringClass()->getName(), $property->getName()));
            }
            $field = new Field($metadata->getClassName(), $property->getName());
            $field->setPublic($property->isPublic());
            $field->setType($type);
            $serializeName = $this->getSerializeName($property);
            if (null !== $serializeName) {
                $field->setSerializeName($serializeName);
            }
            $getter = $metadata->getGetter($property->getName());
            if (null !== $getter) {
                if ($getter->getType()->isUnknown()) {
                    $getter->setType($field->getType());
                }
                $getter->setSerializeName($field->getSerializeName());
            } else {
                $metadata->addGetter($field);
            }
            $setter = $metadata->getSetter($property->getName());
            if (null !== $setter) {
                if ($setter->getType()->isUnknown()) {
                    $setter->setType($field->getType());
                }
                $setter->setSerializeName($field->getSerializeName());
            } elseif (!$property->isReadOnly()) {
                $metadata->addSetter($field);
            }
        }
    }

    protected function isIgnore(ReflectionMethod|ReflectionProperty|ReflectionParameter $reflector): bool
    {
        return count($reflector->getAttributes(SerializeIgnore::class)) > 0;
    }

    protected function getSerializeName(ReflectionMethod|ReflectionProperty|ReflectionParameter $reflector): ?string
    {
        $attributes = $reflector->getAttributes(SerializeName::class);
        if (count($attributes) > 0) {
            /** @var SerializeName $attribute */
            $attribute = $attributes[0]->newInstance();

            return $attribute->getName();
        }

        return null;
    }

    private function isCacheHit(string $className): bool
    {
        return isset($this->cache[$className]);
    }

    private function getCached(string $className): ClassMetadata
    {
        return $this->cache[$className];
    }

    private function save(string $className, ClassMetadata $metadata): ClassMetadata
    {
        return $this->cache[$className] = $metadata;
    }

    private function validateType(ReflectionTypeInterface $type): bool
    {
        return !$type->isResource();
    }
}
