<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use DI\Definition\ObjectDefinition;
use DI\Definition\Reference;
use kuiper\di\ComponentDefinition;
use kuiper\di\ContainerBuilderAwareInterface;
use kuiper\di\ContainerBuilderAwareTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Component implements ComponentInterface, ContainerBuilderAwareInterface
{
    use ComponentTrait;
    use ContainerBuilderAwareTrait;

    /**
     * @var string
     */
    public $value = '';

    public function handle(): void
    {
        if (!empty($this->value)) {
            $names = [$this->value];
        } else {
            $names = $this->getBeanNames();
        }
        $definitions = [];
        $className = $this->class->getName();
        foreach ($names as $name) {
            if ($name === $className) {
                $definition = new ObjectDefinition($className);
            } else {
                $definition = new Reference($className);
            }
            $definitions[$name] = new ComponentDefinition($definition, $this);
        }
        $this->containerBuilder->addDefinitions($definitions);
    }

    protected function getBeanNames(): array
    {
        return array_merge($this->class->getInterfaceNames() ?: [], [$this->class->getName()]);
    }
}
