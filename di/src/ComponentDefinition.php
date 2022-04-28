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

class ComponentDefinition implements Definition
{
    public function __construct(private Definition $definition, private Component $component)
    {
    }

    public function getComponent(): Component
    {
        return $this->component;
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
    public function setName(string $name): void
    {
        $this->definition->setName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceNestedDefinitions(callable $replacer): void
    {
        $this->definition->replaceNestedDefinitions($replacer);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return (string) $this->definition;
    }
}
