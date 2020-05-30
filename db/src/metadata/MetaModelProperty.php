<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

use kuiper\db\annotation\Annotation;
use kuiper\db\converter\AttributeConverterInterface;
use kuiper\reflection\ReflectionTypeInterface;

class MetaModelProperty
{
    /**
     * @var \ReflectionProperty
     */
    private $property;

    /**
     * @var ReflectionTypeInterface
     */
    private $type;

    /**
     * @var MetaModelProperty|null
     */
    private $parent;

    /**
     * @var Annotation[]
     */
    private $annotations;

    /**
     * @var Column|null
     */
    private $column;

    /**
     * @var MetaModelProperty[]
     */
    private $children = [];

    public function __construct(\ReflectionProperty $property, ReflectionTypeInterface $type, ?MetaModelProperty $parent, array $annotations)
    {
        $property->setAccessible(true);
        $this->property = $property;
        $this->type = $type;
        $this->parent = $parent;
        $this->annotations = $annotations;
    }

    public function getName(): string
    {
        return $this->property->getName();
    }

    public function getType(): ReflectionTypeInterface
    {
        return $this->type;
    }

    /**
     * @return MetaModelProperty
     */
    public function getParent(): ?MetaModelProperty
    {
        return $this->parent;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        if ($this->column) {
            return [$this->column];
        }

        return array_merge(...array_map(static function (MetaModelProperty $property) {
            return $property->getColumns();
        }, $this->children));
    }

    public function getValue($entity)
    {
        if ($this->parent) {
            $value = $this->parent->getValue($entity);
            if (!isset($value)) {
                return null;
            }
            $entity = $value;
        }

        return $this->property->getValue($entity);
    }

    protected function getValueOrCreated($entity)
    {
        if (!$this->parent) {
            return $entity;
        }
        $value = $this->parent->getValueOrCreated($entity);
        if (!isset($value)) {
            $value = $this->parent->property->getDeclaringClass()->newInstance();
            $this->parent->setValue($entity, $value);
        }

        return $value;
    }

    public function setValue($entity, $value): void
    {
        if ($this->parent) {
            $entity = $this->parent->getValueOrCreated($entity);
        }
        $this->property->setValue($entity, $value);
    }

    public function getAnnotation(string $annotationName): ?Annotation
    {
        foreach ($this->annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    public function hasAnnotation(string $annotationName): bool
    {
        return null !== $this->getAnnotation($annotationName);
    }

    public function getEntityClass(): string
    {
        return $this->parent ? $this->parent->getEntityClass()
            : $this->property->getDeclaringClass()->getName();
    }

    public function getPath(): string
    {
        return ($this->parent ? $this->parent->getPath().'.' : '').$this->property->getName();
    }

    public function createColumn(string $columnName, ?AttributeConverterInterface $attributeConverter): void
    {
        $this->column = new Column($columnName, $this, $attributeConverter);
    }

    /**
     * @param MetaModelProperty[] $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }
}
