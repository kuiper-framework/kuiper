<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Definition;
use Psr\Container\ContainerInterface;

class ConditionDefinition implements Definition, Condition
{
    /**
     * @var callable
     */
    private $definitionResolver;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var Definition|null
     */
    private $definition;

    /**
     * @var Condition
     */
    private $condition;

    /**
     * ConditionDefinition constructor.
     *
     * @param callable|Definition $definitionResolver
     * @param Condition           $condition
     */
    public function __construct(Condition $condition, $definitionResolver, string $name = null)
    {
        if ($definitionResolver instanceof Definition) {
            $this->definition = $definitionResolver;
        } else {
            $this->definitionResolver = $definitionResolver;
        }
        $this->name = $name;
        $this->condition = $condition;
    }

    public function getDefinition(): Definition
    {
        if (null === $this->definition) {
            $this->definition = call_user_func($this->definitionResolver);
        }

        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        if (null === $this->name) {
            $this->name = $this->getDefinition()->getName();
        }

        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->getDefinition()->setName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceNestedDefinitions(callable $replacer): void
    {
        $this->getDefinition()->replaceNestedDefinitions($replacer);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return (string) $this->getDefinition();
    }

    public function matches(ContainerInterface $container): bool
    {
        return $this->condition->matches($container);
    }
}
