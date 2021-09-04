<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use DI\Definition\AutowireDefinition;
use DI\Definition\Reference;
use kuiper\di\ComponentCollection;
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
        ComponentCollection::register($this);
        $className = $this->class->getName();
        $this->setComponentId($className);
        if (!empty($this->value)) {
            $names = [$this->value];
        } else {
            $names = $this->class->getInterfaceNames();
            if (empty($names)) {
                $names = [$className];
            }
        }
        $definitions = [];
        foreach ($names as $name) {
            if ($name === $className) {
                $definition = new AutowireDefinition($className);
            } else {
                $definition = new Reference($className);
            }
            $definitions[$name] = new ComponentDefinition($definition, $this);
        }
        $this->containerBuilder->addDefinitions($definitions);
    }
}
