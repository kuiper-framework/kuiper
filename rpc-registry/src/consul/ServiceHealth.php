<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

class ServiceHealth
{
    /**
     * @var string
     */
    public $AggregatedStatus;
    /**
     * @var Service
     */
    public $Service;

    /**
     * @var ServiceHealthCheck[]
     */
    public $Checks;
}
