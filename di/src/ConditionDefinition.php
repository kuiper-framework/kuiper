<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Definition;
use Psr\Container\ContainerInterface;

class ConditionDefinition implements Definition, Condition
{
    use DelegateDefinitionTrait;

    /**
     * @var Condition
     */
    private $condition;

    public function __construct(Definition $definition, Condition $condition)
    {
        $this->definition = $definition;
        $this->condition = $condition;
    }

    public function matches(ContainerInterface $container): bool
    {
        return $this->condition->matches($container);
    }
}
