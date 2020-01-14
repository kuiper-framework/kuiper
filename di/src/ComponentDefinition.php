<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Definition;
use kuiper\di\annotation\ComponentInterface;

class ComponentDefinition implements Definition
{
    /**
     * @var Definition
     */
    private $definition;
    /**
     * @var ComponentInterface
     */
    private $component;

    /**
     * ComponentDefintion constructor.
     */
    public function __construct(Definition $definition, ComponentInterface $component)
    {
        $this->definition = $definition;
        $this->component = $component;
    }

    public function getDefinition(): Definition
    {
        return $this->definition;
    }

    public function getComponent(): ComponentInterface
    {
        return $this->component;
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
