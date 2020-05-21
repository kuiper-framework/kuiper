<?php

declare(strict_types=1);

namespace kuiper\db\orm;

use kuiper\db\orm\annotation\Annotation;
use kuiper\reflection\ReflectionTypeInterface;

class ColumnMetadata implements \Serializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $modelClass;

    /**
     * @var \ReflectionProperty
     */
    private $property;

    /**
     * @var callable
     */
    private $getter;

    /**
     * @var callable
     */
    private $setter;

    /**
     * @var string
     */
    private $type;

    /**
     * @var ReflectionTypeInterface
     */
    private $valueType;

    /**
     * @var int
     */
    private $length;

    /**
     * @var string
     */
    private $serializer;

    /**
     * @var Annotation[]
     */
    private $annotations;

    /**
     * @var bool
     */
    private $nullable = false;

    /**
     * @var bool
     */
    private $enumerable = false;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): ColumnMetadata
    {
        $this->name = $name;

        return $this;
    }

    public function getGetter(): callable
    {
        if (!$this->getter) {
            foreach (['get', 'is'] as $prefix) {
                $method = $prefix.$this->property->getName();
                if (method_exists($this->getModelClass(), $method)) {
                    $this->setGetter(function ($object) use ($method) {
                        return $object->$method();
                    });
                    break;
                }
            }
        }

        return $this->getter;
    }

    public function setGetter(callable $getter): ColumnMetadata
    {
        $this->getter = $getter;

        return $this;
    }

    public function getModelClass()
    {
        return $this->modelClass;
    }

    public function setModelClass(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function getSetter(): callable
    {
        if (!$this->setter) {
            if (method_exists($this->getModelClass(), $method = 'set'.$this->property->getName())) {
                $this->setSetter(function ($object, $value) use ($method) {
                    return $object->$method($value);
                });
            }
        }

        return $this->setter;
    }

    public function setSetter(callable $setter): ColumnMetadata
    {
        $this->setter = $setter;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type): ColumnMetadata
    {
        $this->type = $type;

        return $this;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): ColumnMetadata
    {
        $this->length = $length;

        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $nullable): ColumnMetadata
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function isEnumerable(): bool
    {
        return $this->enumerable;
    }

    public function setEnumerable(bool $enumerable): ColumnMetadata
    {
        $this->enumerable = $enumerable;

        return $this;
    }

    public function getProperty(): \ReflectionProperty
    {
        return $this->property;
    }

    public function setProperty(\ReflectionProperty $property): ColumnMetadata
    {
        $this->property = $property;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $vars = get_object_vars($this);
        foreach (['getter', 'setter'] as $name) {
            unset($vars[$name]);
        }

        return serialize($vars);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $key => $value) {
            $this->$key = $value;
        }
    }

    public function setValueType(ReflectionTypeInterface $valueType): ColumnMetadata
    {
        $this->valueType = $valueType;

        return $this;
    }

    public function getValueType(): ReflectionTypeInterface
    {
        return $this->valueType;
    }

    /**
     * @return string
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    public function setSerializer(string $serializer): ColumnMetadata
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @return Annotation[]
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * @param Annotation[] $annotations
     *
     * @return ColumnMetadata
     */
    public function setAnnotations($annotations)
    {
        $this->annotations = $annotations;

        return $this;
    }

    public function addAnnotation(Annotation $annotation)
    {
        $this->annotations[] = $annotation;

        return $this;
    }

    public function hasAnnotation($annotationClass)
    {
        $annotation = $this->getAnnotation($annotationClass);

        return isset($annotation);
    }

    public function getAnnotation($annotationClass)
    {
        foreach ($this->annotations as $annotation) {
            if ($annotation instanceof $annotationClass) {
                return $annotation;
            }
        }
    }
}
