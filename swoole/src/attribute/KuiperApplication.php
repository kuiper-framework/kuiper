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

namespace kuiper\swoole\attribute;

use kuiper\di\attribute\ComponentTrait;
use kuiper\di\Component;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;

#[\Attribute(\Attribute::TARGET_CLASS)]
class KuiperApplication implements Component
{
    use ComponentTrait;
    use ContainerBuilderAwareTrait;

    public function __construct(
        private readonly array $exclude,
        private readonly array $excludeNamespaces,
        private readonly array $priorities)
    {
    }

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
