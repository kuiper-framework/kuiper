<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

class RegisterServiceRequest
{
    /**
     * @var string|null
     */
    public $ID;

    /**
     * @var string
     */
    public $Name;
    /**
     * @var string[]
     */
    public $Tags;
    /**
     * @var string
     */
    public $Address;
    /**
     * @var int
     */
    public $Port;
    /**
     * @var string|string[]
     */
    public $TaggedAddresses;
    /**
     * @var string[]
     */
    public $Meta;
    /**
     * @var string
     */
    public $Kind = '';
    /**
     * @var bool
     */
    public $EnableTagOverride;
    /**
     * @var RegisterServiceCheck
     */
    public $Check;
    /**
     * @var RegisterServiceCheck[]
     */
    public $Checks;

    /**
     * @var ServiceWeight
     */
    public $Weights;
}
