<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use DI\Definition\Reference;
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
        foreach ($names as $name) {
            $definitions[$name] = new Reference($this->class->getName());
        }
        $this->containerBuilder->addDefinitions($definitions);
    }

    protected function getBeanNames(): array
    {
        return [$this->class->getName()];
    }
}
