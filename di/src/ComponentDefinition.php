<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Definition;
use kuiper\di\annotation\ComponentInterface;

class ComponentDefinition implements Definition
{
    use DelegateDefinitionTrait;

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

    public function getComponent(): ComponentInterface
    {
        return $this->component;
    }
}
