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

namespace kuiper\tars\type;

class StructType extends AbstractType
{
    /**
     * @param StructField[] $fields
     */
    public function __construct(private readonly string $className, private array $fields, private readonly bool $constructorBased)
    {
    }

    /**
     * @return bool
     */
    public function isConstructorBased(): bool
    {
        return $this->constructorBased;
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
