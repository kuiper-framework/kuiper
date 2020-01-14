<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Definition;
use kuiper\di\annotation\Conditional;
use Psr\Container\ContainerInterface;

class ConditionalDefinition implements Definition, Conditional
{
    /**
     * @var Definition
     */
    private $definition;
    /**
     * @var callable
     */
    private $condition;

    public function __construct(Definition $definition, Conditional $condition)
    {
        $this->definition = $definition;
        $this->condition = $condition;
    }

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
    public function __toString()
    {
        return (string) $this->definition;
    }

    public function match(ContainerInterface $container): bool
    {
        return $this->condition->match($container);
    }
}
