<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

use kuiper\db\AttributeConverterInterface;

class Column
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var \ReflectionProperty[]
     */
    private $propertyPath;

    /**
     * @var AttributeConverterInterface
     */
    private $converter;

    /**
     * @var bool
     */
    private $id;

    /**
     * @var string
     */
    private $generateStrategy;

    /**
     * @var bool
     */
    private $naturalId;

    /**
     * @var bool
     */
    private $creationTimestamp;

    /**
     * @var bool
     */
    private $updateTimestamp;

    /**
     * Column constructor.
     *
     * @param \ReflectionProperty[] $propertyPath
     */
    public function __construct(string $name, array $propertyPath, AttributeConverterInterface $converter,
                                bool $id, string $generateStrategy, bool $naturalId,
                                bool $creationTimestamp, bool $updateTimestamp)
    {
        $this->name = $name;
        $this->propertyPath = $propertyPath;
        $this->converter = $converter;
        $this->id = $id;
        $this->generateStrategy = $generateStrategy;
        $this->naturalId = $naturalId;
        $this->creationTimestamp = $creationTimestamp;
        $this->updateTimestamp = $updateTimestamp;
    }

    public function getValue($entity)
    {
        $value = $this->getPropertyValue($entity, -1);

        return $this->converter->convertToDatabaseColumn($value, $this);
    }

    public function getPropertyValue($entity, int $level = 0)
    {
        if (-1 === $level) {
            $level = count($this->propertyPath) - 1;
        }
        if ($level > count($this->propertyPath) - 1) {
            throw new \InvalidArgumentException("Cannot set property of level $level");
        }
        $value = $entity;
        for ($i = 0; $i <= $level; ++$i) {
            $property = $this->propertyPath[$i];
            $value = $property->getValue($value);
        }

        return $value;
    }

    public function setValue($entity, $value): void
    {
        $attributeValue = $this->converter->convertToEntityAttribute($value, $this);
        $propertyValue = $entity;
        $level = count($this->propertyPath) - 1;
        for ($i = 0; $i < $level; ++$i) {
            $property = $this->propertyPath[$i];
            $theValue = $property->getValue($propertyValue);
            if (!isset($theValue)) {
                $class = $property->getDeclaringClass()->getName();
                $theValue = new $class();
                $property->setValue($propertyValue, $theValue);
            }
            $propertyValue = $theValue;
        }
        $this->propertyPath[$level]->setValue($propertyValue, $attributeValue);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isId(): bool
    {
        return $this->id;
    }

    public function isGeneratedValue(): bool
    {
        return isset($this->generateStrategy);
    }

    public function isNaturalId(): bool
    {
        return $this->naturalId;
    }

    public function isCreationTimestamp(): bool
    {
        return $this->creationTimestamp;
    }

    public function isUpdateTimestamp(): bool
    {
        return $this->updateTimestamp;
    }
}
