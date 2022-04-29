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
     * @var Definition|null
     */
    private $definition;

    public function __construct(
        private readonly Condition $condition,
        Definition|callable $definitionResolver,
        private ?string $name = null)
    {
        if ($definitionResolver instanceof Definition) {
            $this->definition = $definitionResolver;
        } else {
            $this->definitionResolver = $definitionResolver;
        }
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
