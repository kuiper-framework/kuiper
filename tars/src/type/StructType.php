<?php

declare(strict_types=1);

namespace kuiper\tars\type;

class StructType extends AbstractType
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var StructField[]
     */
    private $fields;

    /**
     * StructType constructor.
     */
    public function __construct(string $className, array $fields)
    {
        $this->className = $className;
        $this->fields = $fields;
    }

    /**
     * @param StructField[] $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    /**
     * @return StructField[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function asStructType(): StructType
    {
        return $this;
    }

    public function isStruct(): bool
    {
        return true;
    }

    public function getTarsType(): int
    {
        return Type::STRUCT_BEGIN;
    }

    public function __toString(): string
    {
        return $this->className;
    }
}
