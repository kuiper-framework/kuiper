<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Definition;
use kuiper\di\annotation\Conditional;
use Psr\Container\ContainerInterface;

class ConditionalDefinition implements Definition, Conditional
{
    use DelegateDefinitionTrait;
    /**
     * @var callable
     */
    private $condition;

    public function __construct(Definition $definition, Conditional $condition)
    {
        $this->definition = $definition;
        $this->condition = $condition;
    }

    public function match(ContainerInterface $container): bool
    {
        return $this->condition->match($container);
    }
}
