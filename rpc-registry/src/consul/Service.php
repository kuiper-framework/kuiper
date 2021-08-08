<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

/**
 * @see https://www.consul.io/docs/discovery/services
 */
class Service
{
    /**
     * @var string
     */
    public $ID;

    /**
     * @var string
     */
    public $Service;

    /**
     * @var string[]
     */
    public $Tags;

    /**
     * @var string[]
     */
    public $TaggedAddresses;

    /**
     * @var string[]
     */
    public $Meta;

    /**
     * @var string
     */
    public $Namespace;
    /**
     * @var int
     */
    public $Port;
    /**
     * @var string
     */
    public $Address;
    /**
     * @var bool
     */
    public $EnableTagOverride;
    /**
     * @var string
     */
    public $Datacenter;
    /**
     * @var ServiceWeight
     */
    public $Weights;
}
