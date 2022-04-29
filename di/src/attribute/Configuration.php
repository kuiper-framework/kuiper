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
use kuiper\di\Component;
use kuiper\di\ContainerBuilderAwareInterface;
use kuiper\di\ContainerBuilderAwareTrait;

#[Attribute(Attribute::TARGET_CLASS)]
class Configuration implements Component, ContainerBuilderAwareInterface
{
    use ComponentTrait;
    use ContainerBuilderAwareTrait;

    /**
     * Configuration constructor.
     * @param string[] $dependOn
     */
    public function __construct(private readonly array $dependOn = [])
    {
    }

    /**
     * @return string[]
     */
    public function getDependOn(): array
    {
        return $this->dependOn;
    }

    public function handle(): void
    {
        $this->containerBuilder->addConfiguration($this->class->newInstance());
    }
}
