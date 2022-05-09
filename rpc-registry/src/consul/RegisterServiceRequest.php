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
    public ?string $ID = null;

    public ?string $Name = null;
    /**
     * @var string[]
     */
    public ?array $Tags = [];

    public ?string $Address = null;

    public ?int $Port = null;
    /**
     * @var string|string[]
     */
    public string|array|null $TaggedAddresses = null;
    /**
     * @var string[]
     */
    public ?array $Meta = null;

    public string $Kind = '';

    public bool $EnableTagOverride = false;

    public ?RegisterServiceCheck $Check = null;
    /**
     * @var RegisterServiceCheck[]
     */
    public ?array $Checks = null;

    public ?ServiceWeight $Weights = null;
}
