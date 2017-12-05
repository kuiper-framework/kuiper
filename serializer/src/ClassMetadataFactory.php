<?php

namespace kuiper\serializer;

use kuiper\annotations\DocReaderInterface;
use kuiper\annotations\ReaderInterface;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\TypeUtils;
use kuiper\serializer\annotation\SerializeIgnore;
use kuiper\serializer\annotation\SerializeName;
use kuiper\serializer\exception\NotSerializableException;

class ClassMetadataFactory
{
    /**
     * @var ReaderInterface
     */
    private $annotationReader;
    /**
     * @var DocReaderInterface
     */
    private $docReader;
    /**
     * @var array
     */
    private $cache;

    /**
     * ClassMetadataFactory constructor.
     *
     * @param ReaderInterface    $annotationReader
     * @param DocReaderInterface $docReader
     */
    public function __construct(ReaderInterface $annotationReader, DocReaderInterface $docReader)
    {
        $this->annotationReader = $annotationReader;
        $this->docReader = $docReader;
    }

    /**
     * gets class properties metadata.
     *
     * @param string $className
     *
     * @return ClassMetadata
     */
    public function create(string $className)
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

    public function clearCache(string $className = null)
    {
        if (isset($className)) {
            unset($this->cache[$className]);
        } else {
            $this->cache = [];
        }
    }

    private function parseMethods(\ReflectionClass $class, ClassMetadata $metadata)
    {
        $isException = $class->isSubclassOf(\Exception::class);
        foreach ($class->getMethods() as $method) {
            if ($method->isStatic() || !$method->isPublic()) {
                continue;
            }
            // ignore trace and traceAsString for Exception class
            if ($isException && in_array($method->getName(), ['getTrace', 'getTraceAsString'])) {
                continue;
            }
            $this->parseSetter($method, $metadata);
            $this->parseGetter($method, $metadata);
        }
    }

    protected function parseSetter(\ReflectionMethod $method, ClassMetadata $metadata)
    {
        $name = $method->getName();
        if (strpos($name, 'set') === 0
            && $method->getNumberOfParameters() === 1
            && !$this->isIgnore($method)) {
            $types = array_values($this->docReader->getParameterTypes($method));
            if (!$this->validateType($types[0])) {
                throw new NotSerializableException(sprintf(
                    'Cannot serialize class %s for method %s',
                    $method->getDeclaringClass()->getName(),
                    $method->getName()
                ));
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

    protected function parseGetter(\ReflectionMethod $method, ClassMetadata $metadata)
    {
        $name = $method->getName();
        if (preg_match('/^(get|is|has)(.+)/', $name, $matches)
            && $method->getNumberOfParameters() === 0
            && !$this->isIgnore($method)) {
            $type = $this->docReader->getReturnType($method);
            if (!$this->validateType($type)) {
                throw new NotSerializableException(sprintf(
                    'Cannot serialize class %s for method %s',
                    $method->getDeclaringClass()->getName(),
                    $method->getName()
                ));
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

    private function parseProperties(\ReflectionClass $class, ClassMetadata $metadata)
    {
        foreach ($class->getProperties() as $property) {
            if ($property->isStatic() || $this->isIgnore($property)) {
                continue;
            }
            $type = $this->docReader->getPropertyType($property);
            if (!$this->validateType($type)) {
                throw new NotSerializableException(sprintf(
                    'Cannot serialize class %s for property %s',
                    $property->getDeclaringClass()->getName(),
                    $property->getName()
                ));
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
                if (TypeUtils::isUnknown($getter->getType())) {
                    $getter->setType($field->getType());
                }
            } else {
                $metadata->addGetter($field);
            }
            $setter = $metadata->getSetter($property->getName());
            if ($setter) {
                if (TypeUtils::isUnknown($setter->getType())) {
                    $setter->setType($field->getType());
                }
            } else {
                $metadata->addSetter($field);
            }
        }
    }

    protected function isIgnore($reflector)
    {
        if ($reflector instanceof \ReflectionMethod) {
            $annotation = $this->annotationReader->getMethodAnnotation($reflector, SerializeIgnore::class);
        } else {
            $annotation = $this->annotationReader->getPropertyAnnotation($reflector, SerializeIgnore::class);
        }

        return $annotation !== null;
    }

    protected function getSerializeName($reflector)
    {
        if ($reflector instanceof \ReflectionMethod) {
            $annotation = $this->annotationReader->getMethodAnnotation($reflector, SerializeName::class);
        } else {
            $annotation = $this->annotationReader->getPropertyAnnotation($reflector, SerializeName::class);
        }

        return $annotation !== null ? $annotation->value : null;
    }

    private function isCacheHit(string $className)
    {
        return isset($this->cache[$className]);
    }

    private function getCached(string $className)
    {
        return $this->cache[$className];
    }

    private function save(string $className, $metadata)
    {
        return $this->cache[$className] = $metadata;
    }

    private function validateType(ReflectionTypeInterface $type)
    {
        return !TypeUtils::isResource($type);
    }
}
