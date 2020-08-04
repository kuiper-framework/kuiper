<?php

declare(strict_types=1);

namespace kuiper\serializer;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\serializer\annotation\SerializeIgnore;
use kuiper\serializer\annotation\SerializeName;
use kuiper\serializer\exception\NotSerializableException;

class ClassMetadataFactory
{
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;
    /**
     * @var DocReaderInterface
     */
    private $docReader;
    /**
     * @var array<string,ClassMetadata>
     */
    private $cache;

    public function __construct(AnnotationReaderInterface $annotationReader, DocReaderInterface $docReader)
    {
        $this->annotationReader = $annotationReader;
        $this->docReader = $docReader;
    }

    /**
     * gets class properties metadata.
     */
    public function create(string $className): ClassMetadata
    {
        if ($this->isCacheHit($className)) {
            return $this->getCached($className);
        }
        $metadata = new ClassMetadata($className);
        $class = new \ReflectionClass($className);
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

    private function parseMethods(\ReflectionClass $class, ClassMetadata $metadata): void
    {
        $isException = $class->isSubclassOf(\Exception::class);
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

    protected function parseSetter(\ReflectionMethod $method, ClassMetadata $metadata): void
    {
        $name = $method->getName();
        if (0 === strpos($name, 'set')
            && 1 === $method->getNumberOfParameters()
            && !$this->isIgnore($method)) {
            $types = array_values($this->docReader->getParameterTypes($method));
            if (!$this->validateType($types[0])) {
                throw new NotSerializableException(sprintf('Cannot serialize class %s for method %s', $method->getDeclaringClass()->getName(), $method->getName()));
            }
            $field = new Field($metadata->getClassName(), lcfirst(substr($name, 3)));
            $field->setType($types[0]);
            $field->setSetter($name);
            $serializeName = $this->getSerializeName($method);
            if ($serializeName) {
                $field->setSerializeName($serializeName);
            }
            $metadata->addSetter($field);
        }
    }

    protected function parseGetter(\ReflectionMethod $method, ClassMetadata $metadata): void
    {
        $name = $method->getName();
        if (preg_match('/^(get|is|has)(.+)/', $name, $matches)
            && 0 === $method->getNumberOfParameters()
            && !$this->isIgnore($method)) {
            $type = $this->docReader->getReturnType($method);
            if (!$this->validateType($type)) {
                throw new NotSerializableException(sprintf('Cannot serialize class %s for method %s', $method->getDeclaringClass()->getName(), $method->getName()));
            }
            $field = new Field($metadata->getClassName(), lcfirst($matches[2]));
            $field->setType($type);
            $field->setGetter($name);
            $serializeName = $this->getSerializeName($method);
            if ($serializeName) {
                $field->setSerializeName($serializeName);
            }
            $metadata->addGetter($field);
        }
    }

    private function parseProperties(\ReflectionClass $class, ClassMetadata $metadata): void
    {
        foreach ($class->getProperties() as $property) {
            if ($property->isStatic() || $this->isIgnore($property)) {
                continue;
            }
            $type = $this->docReader->getPropertyType($property);
            if (!$this->validateType($type)) {
                throw new NotSerializableException(sprintf('Cannot serialize class %s for property %s', $property->getDeclaringClass()->getName(), $property->getName()));
            }
            $field = new Field($metadata->getClassName(), $property->getName());
            $field->setIsPublic($property->isPublic())
                ->setType($type);
            $serializeName = $this->getSerializeName($property);
            if ($serializeName) {
                $field->setSerializeName($serializeName);
            }
            $getter = $metadata->getGetter($property->getName());
            if ($getter) {
                if ($getter->getType()->isUnknown()) {
                    $getter->setType($field->getType());
                }
            } else {
                $metadata->addGetter($field);
            }
            $setter = $metadata->getSetter($property->getName());
            if ($setter) {
                if ($setter->getType()->isUnknown()) {
                    $setter->setType($field->getType());
                }
            } else {
                $metadata->addSetter($field);
            }
        }
    }

    protected function isIgnore($reflector): bool
    {
        if ($reflector instanceof \ReflectionMethod) {
            $annotation = $this->annotationReader->getMethodAnnotation($reflector, SerializeIgnore::class);
        } else {
            $annotation = $this->annotationReader->getPropertyAnnotation($reflector, SerializeIgnore::class);
        }

        return null !== $annotation;
    }

    protected function getSerializeName($reflector): ?string
    {
        if ($reflector instanceof \ReflectionMethod) {
            $annotation = $this->annotationReader->getMethodAnnotation($reflector, SerializeName::class);
        } else {
            $annotation = $this->annotationReader->getPropertyAnnotation($reflector, SerializeName::class);
        }

        return null !== $annotation ? $annotation->value : null;
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
