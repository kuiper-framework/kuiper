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

namespace kuiper\di\attribute;

use Attribute;
use DI\Definition\AutowireDefinition;
use DI\Definition\Exception\InvalidAttribute;
use DI\Definition\Reference;
use kuiper\di\ComponentCollection;
use kuiper\di\ComponentDefinition;
use kuiper\di\ContainerBuilderAwareInterface;
use kuiper\di\ContainerBuilderAwareTrait;

#[Attribute(Attribute::TARGET_CLASS)]
class Component implements \kuiper\di\Component, ContainerBuilderAwareInterface
{
    use ComponentTrait;
    use ContainerBuilderAwareTrait;

    public function __construct(private readonly ?string $value = null)
    {
    }

    /**
     * @throws InvalidAttribute
     */
    public function handle(): void
    {
        ComponentCollection::register($this);
        if (!$this->class->isInstantiable()) {
            throw new InvalidAttribute(sprintf('Cannot put attribute %s on %s, because %2$s cannot be instantiable', get_class($this), $this->class->getName()));
        }
        $className = $this->class->getName();
        if (!empty($this->value)) {
            $names = [$this->value];
        } else {
            $names = $this->class->getInterfaceNames();
            if (empty($names)) {
                $names = [$className];
            }
        }
        $this->setComponentId($names[0]);
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
