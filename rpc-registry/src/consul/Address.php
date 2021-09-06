<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

class Address
{
    /**
     * @var string
     */
    public $address;

    /**
     * @var int
     */
    public $port;
}
