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

namespace kuiper\db\metadata;

use kuiper\db\annotation\Annotation;
use kuiper\db\converter\AttributeConverterInterface;
use kuiper\db\exception\MetaModelException;
use kuiper\reflection\ReflectionTypeInterface;

class MetaModelProperty
{
    public const PATH_SEPARATOR = '.';

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

    /**
     * @var \ReflectionClass
     */
    private $modelClass;

    /**
     * @var string
     */
    private $path;

    /**
     * @var MetaModelProperty[]
     */
    private $ancestors;

    public function __construct(\ReflectionProperty $property, ReflectionTypeInterface $type, ?MetaModelProperty $parent, array $annotations)
    {
        $property->setAccessible(true);
        $this->property = $property;
        $this->type = $type;
        $this->parent = $parent;
        $this->annotations = $annotations;
        $this->path = (null !== $parent ? $parent->getPath().self::PATH_SEPARATOR : '').$property->getName();
        $this->ancestors = $this->buildAncestors();
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
        if (null !== $this->column) {
            return [$this->column];
        }

        return array_merge(...array_map(static function (MetaModelProperty $property): array {
            return $property->getColumns();
        }, array_values($this->children)));
    }

    /**
     * @param mixed $propertyValue
     */
    public function getColumnValues($propertyValue): array
    {
        if (null !== $this->column) {
            return [
                $this->column->getName() => $this->column->getConverter()
                    ->convertToDatabaseColumn($propertyValue, $this->column),
            ];
        }
        if (!is_object($propertyValue)) {
            throw new \InvalidArgumentException("Expected {$this->getFullName()} type of {$this->type}, got ".gettype($propertyValue));
        }
        if ($this->type->isClass() && !is_a($propertyValue, $this->type->getName())) {
            throw new \InvalidArgumentException("Expected {$this->getFullName()} type of {$this->type}, got ".get_class($propertyValue));
        }

        return array_merge(...array_map(static function (MetaModelProperty $child) use ($propertyValue): array {
            return $child->getColumnValues($child->property->getValue($propertyValue));
        }, array_values($this->children)));
    }

    /**
     * @param object $entity
     *
     * @return mixed|null
     */
    public function getValue($entity)
    {
        if (null !== $this->parent) {
            $value = $this->parent->getValue($entity);
            if (!isset($value)) {
                return null;
            }
            $entity = $value;
        }

        return $this->property->getValue($entity);
    }

    /**
     * @param object $entity
     * @param mixed  $value
     */
    public function setValue($entity, $value): void
    {
        $model = $entity;
        foreach ($this->ancestors as $path) {
            $propertyValue = $path->property->getValue($model);
            if (!isset($propertyValue)) {
                $propertyValue = $path->modelClass->newInstanceWithoutConstructor();
                $path->property->setValue($model, $propertyValue);
            }
            $model = $propertyValue;
        }

        $this->property->setValue($model, $value);
    }

    public function getAnnotation(string $annotationName): ?Annotation
    {
        foreach ($this->annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null !== $this->parent ? $this->parent->getAnnotation($annotationName) : null;
    }

    public function hasAnnotation(string $annotationName): bool
    {
        return null !== $this->getAnnotation($annotationName);
    }

    public function getEntityClass(): \ReflectionClass
    {
        return null !== $this->parent
            ? $this->parent->getEntityClass()
            : $this->property->getDeclaringClass();
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFullName(): string
    {
        return $this->getEntityClass()->getName().'.'.$this->getPath();
    }

    public function getSubProperty(string $path): ?MetaModelProperty
    {
        $parts = explode(self::PATH_SEPARATOR, $path, 2);
        if (!isset($this->children[$parts[0]])) {
            return null;
        }
        if (1 === count($parts)) {
            return $this->children[$path] ?? null;
        }

        return $this->children[$parts[0]]->getSubProperty($parts[1]);
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
        foreach ($children as $child) {
            $this->children[$child->getName()] = $child;
        }
    }

    private function buildAncestors(): array
    {
        $ancestors = [];
        $metaProperty = $this->parent;
        while (null !== $metaProperty) {
            if (null === $metaProperty->modelClass) {
                if (!$metaProperty->type->isClass()) {
                    throw new MetaModelException($metaProperty->type.' not class');
                }
                $metaProperty->modelClass = new \ReflectionClass($metaProperty->type->getName());
            }
            $ancestors[] = $metaProperty;
            $metaProperty = $metaProperty->parent;
        }

        return $ancestors;
    }
}
