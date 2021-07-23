<?php

declare(strict_types=1);

namespace kuiper\serializer;

class ClassMetadata
{
    /**
     * @var string
     */
    private $className;
    /**
     * @var array
     */
    private $getters = [];

    /**
     * @var array
     */
    private $setters = [];

    /**
     * ClassMetadata constructor.
     */
    public function __construct(string $className)
    {
        $this->className = $className;
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
}
