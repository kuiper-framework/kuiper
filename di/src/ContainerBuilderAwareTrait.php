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

namespace kuiper\di;

trait ContainerBuilderAwareTrait
{
    /**
     * @var ContainerBuilderInterface
     */
    protected $containerBuilder;

    public function setContainerBuilder(ContainerBuilderInterface $containerBuilder): void
    {
        $this->containerBuilder = $containerBuilder;
    }
}
