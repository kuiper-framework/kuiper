<?php

declare(strict_types=1);

namespace kuiper\swoole\annotation;

use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class KuiperApplication implements ComponentInterface
{
    use ComponentTrait;
    use ContainerBuilderAwareTrait;

    /**
     * @var array
     */
    public $exclude;

    /**
     * @var array
     */
    public $excludeNamespaces;

    /**
     * @var array
     */
    public $priorities;

    public function handle(): void
    {
        ComponentCollection::register($this);
        foreach ($this->exclude ?? []  as $configuration) {
            $this->containerBuilder->removeConfiguration($configuration);
        }
        foreach ($this->excludeNamespaces ?? [] as $namespace) {
            $this->containerBuilder->componentScanExclude($namespace);
        }
        if (!empty($this->priorities)) {
            $this->containerBuilder->setConfigurationPriorities($this->priorities);
        }
    }
}
