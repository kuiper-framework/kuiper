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

use kuiper\db\attribute\CreationTimestamp;
use kuiper\db\attribute\GeneratedValue;
use kuiper\db\attribute\Id;
use kuiper\db\attribute\NaturalId;
use kuiper\db\attribute\UpdateTimestamp;
use kuiper\db\converter\AttributeConverterInterface;
use kuiper\reflection\ReflectionTypeInterface;

class Column implements ColumnInterface
{
    private bool $hasId;

    private bool $hasNaturalId;

    private bool $hasCreationTimestamp;

    private bool $hasUpdateTimestamp;

    private ?string $generateStrategy;

    public function __construct(
        private readonly string $name,
        private readonly MetaModelProperty $property,
        private readonly AttributeConverterInterface $converter)
    {
        $this->hasId = $property->hasAttribute(Id::class);
        $this->hasNaturalId = $property->hasAttribute(NaturalId::class);
        $this->hasCreationTimestamp = $property->hasAttribute(CreationTimestamp::class);
        $this->hasUpdateTimestamp = $property->hasAttribute(UpdateTimestamp::class);
        $generatedValue = $property->getAttribute(GeneratedValue::class);
        if (null !== $generatedValue) {
            $this->generateStrategy = $generatedValue->getType();
        }
    }

    public function getProperty(): MetaModelProperty
    {
        return $this->property;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPropertyPath(): string
    {
        return $this->property->getPath();
    }

    public function getValue(object $entity): mixed
    {
        $value = $this->property->getValue($entity);
        if ($this->isNull($value)) {
            return $value;
        }

        return $this->converter->convertToDatabaseColumn($value, $this);
    }

    public function setValue(object $entity, mixed $value): void
    {
        $attributeValue = isset($value) ? $this->converter->convertToEntityAttribute($value, $this) : null;
        $this->property->setValue($entity, $attributeValue);
    }

    public function getConverter(): AttributeConverterInterface
    {
        return $this->converter;
    }

    public function getType(): ReflectionTypeInterface
    {
        return $this->property->getType();
    }

    public function hasId(): bool
    {
        return $this->hasId;
    }

    public function hasGeneratedValue(): bool
    {
        return isset($this->generateStrategy);
    }

    public function hasNaturalId(): bool
    {
        return $this->hasNaturalId;
    }

    public function hasCreationTimestamp(): bool
    {
        return $this->hasCreationTimestamp;
    }

    public function hasUpdateTimestamp(): bool
    {
        return $this->hasUpdateTimestamp;
    }

    private function isNull(mixed $value): bool
    {
        return !isset($value) || $value instanceof NullValue;
    }
}
