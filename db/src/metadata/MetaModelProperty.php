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

use InvalidArgumentException;
use kuiper\db\attribute\Attribute;
use kuiper\db\converter\AttributeConverterInterface;
use kuiper\db\exception\MetaModelException;
use kuiper\reflection\ReflectionTypeInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class MetaModelProperty
{
    public const PATH_SEPARATOR = '.';

    private ?Column $column = null;

    /**
     * @var MetaModelProperty[]
     */
    private array $children = [];

    private ?ReflectionClass $modelClass = null;

    private readonly string $path;

    /**
     * @var MetaModelProperty[]
     */
    private readonly array $ancestors;

    /**
     * @param ReflectionProperty      $property
     * @param ReflectionTypeInterface $type
     * @param MetaModelProperty|null  $parent
     * @param Attribute[]             $attributes
     *
     * @throws MetaModelException|ReflectionException
     */
    public function __construct(
        private readonly ReflectionProperty $property,
        private readonly ReflectionTypeInterface $type,
        private readonly ?MetaModelProperty $parent,
        private readonly array $attributes)
    {
        $this->path = (null !== $parent ? $parent->getPath().self::PATH_SEPARATOR : '').$property->getName();

        $ancestors = [];
        $metaProperty = $this->parent;
        while (null !== $metaProperty) {
            if (null === $metaProperty->modelClass) {
                if (!$metaProperty->type->isClass()) {
                    throw new MetaModelException($metaProperty->type.' not class');
                }
                $metaProperty->modelClass = new ReflectionClass($metaProperty->type->getName());
            }
            $ancestors[] = $metaProperty;
            $metaProperty = $metaProperty->parent;
        }

        $this->ancestors = $ancestors;
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
    public function getColumnValues(mixed $propertyValue): array
    {
        if (null !== $this->column) {
            return [
                $this->column->getName() => isset($propertyValue)
                    ? $this->column->getConverter()->convertToDatabaseColumn($propertyValue, $this->column)
                    : null,
            ];
        }
        if (!is_object($propertyValue)) {
            throw new InvalidArgumentException("Expected {$this->getFullName()} type of {$this->type}, got ".gettype($propertyValue));
        }
        if ($this->type->isClass() && !is_a($propertyValue, $this->type->getName())) {
            throw new InvalidArgumentException("Expected {$this->getFullName()} type of {$this->type}, got ".get_class($propertyValue));
        }

        return array_merge(...array_map(static function (MetaModelProperty $child) use ($propertyValue): array {
            return $child->getColumnValues($child->property->getValue($propertyValue));
        }, array_values($this->children)));
    }

    public function getValue(object $entity): mixed
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

    public function setValue(object $entity, mixed $value): void
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

    /**
     * @template T
     *
     * @param class-string<T> $attributeName
     *
     * @return T|null
     */
    public function getAttribute(string $attributeName)
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute instanceof $attributeName) {
                return $attribute;
            }
        }

        return $this->parent?->getAttribute($attributeName);
    }

    public function hasAttribute(string $annotationName): bool
    {
        return null !== $this->getAttribute($annotationName);
    }

    public function getEntityClass(): ReflectionClass
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
}
