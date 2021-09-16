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
