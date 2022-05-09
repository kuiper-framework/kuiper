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

namespace kuiper\tars\server\monitor\collector;

use kuiper\tars\server\monitor\MetricPolicy;
use kuiper\tars\server\ServerProperties;

abstract class AbstractCollector implements CollectorInterface
{
    public function __construct(private readonly ServerProperties $serverProperties)
    {
    }

    public function getServerProperties(): ServerProperties
    {
        return $this->serverProperties;
    }

    public function getServerName(): string
    {
        return $this->serverProperties->getServerName();
    }

    /**
     * {@inheritDoc}
     */
    public function getPolicy(): string
    {
        return MetricPolicy::MAX;
    }
}
