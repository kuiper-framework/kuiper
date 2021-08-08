<?php

declare(strict_types=1);

namespace kuiper\tars\server\monitor\collector;

use kuiper\tars\server\monitor\MetricPolicy;
use kuiper\tars\server\ServerProperties;

abstract class AbstractCollector implements CollectorInterface
{
    /**
     * @var ServerProperties
     */
    private $serverProperties;

    public function __construct(ServerProperties $serverProperties)
    {
        $this->serverProperties = $serverProperties;
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
