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

namespace kuiper\serializer;

class ClassMetadata
{
    /**
     * @var Field[]
     */
    private array $getters = [];

    /**
     * @var Field[]
     */
    private array $setters = [];

    /**
     * @var Field[]
     */
    private array $constructorArgs = [];

    /**
     * ClassMetadata constructor.
     */
    public function __construct(private readonly string $className)
    {
    }

    /**
     * @return bool
     */
    public function hasConstructor(): bool
    {
        return !empty($this->constructorArgs);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function addSetter(Field $field): void
    {
        $this->setters[$field->getName()] = $field;
    }

    public function addGetter(Field $field): void
    {
        $this->getters[$field->getName()] = $field;
    }

    public function addConstructorArg(Field $field): void
    {
        $this->constructorArgs[$field->getName()] = $field;
    }

    public function getSetter(string $name): ?Field
    {
        return $this->setters[$name] ?? null;
    }

    public function getGetter(string $name): ?Field
    {
        return $this->getters[$name] ?? null;
    }

    /**
     * @return Field[]
     */
    public function getGetters(): array
    {
        return array_values($this->getters);
    }

    /**
     * @return Field[]
     */
    public function getSetters(): array
    {
        return array_values($this->setters);
    }

    /**
     * @return array
     */
    public function getConstructorArgs(): array
    {
        return $this->constructorArgs;
    }
}
