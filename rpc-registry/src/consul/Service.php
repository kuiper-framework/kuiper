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
     * @var Address[]
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
     * @var int[]
     */
    public $Weights;
}
