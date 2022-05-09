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
    public ?string $ID = null;

    public ?string $Service = null;

    public array $Tags = [];

    public array $TaggedAddresses = [];

    public array $Meta = [];

    public ?string $Namespace = null;

    public ?int $Port = null;

    public ?string $Address = null;

    public bool $EnableTagOverride = false;

    public ?string $Datacenter = null;

    public array $Weights = [];
}
