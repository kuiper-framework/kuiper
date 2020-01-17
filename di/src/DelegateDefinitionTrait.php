<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Definition;

trait DelegateDefinitionTrait
{
    /**
     * @var Definition
     */
    protected $definition;

    public function getDefinition(): Definition
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->definition->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name)
    {
        return $this->definition->setName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceNestedDefinitions(callable $replacer)
    {
        return $this->definition->replaceNestedDefinitions($replacer);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return (string) $this->definition;
    }
}
