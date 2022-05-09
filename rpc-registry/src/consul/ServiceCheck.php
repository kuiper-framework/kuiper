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
 * @see https://www.consul.io/api/agent/check
 */
class ServiceCheck
{
    public ?string $Name = null;

    public ?string $ID = null;

    public ?string $DeregisterCriticalServiceAfter = null;
    /**
     * @var string[]
     */
    public ?array $Args = null;

    public ?string $Interval = null;

    public ?string $Timeout = null;

    /**
     * A URL to perform http check.
     *
     * Specifies an HTTP check to perform a GET request against the
     * value of HTTP (expected to be a URL) every Interval. If the
     * response is any 2xx code, the check is passing. If the response
     * is 429 Too Many Requests, the check is warning. Otherwise, the
     * check is critical. HTTP checks also support SSL. By default, a
     * valid SSL certificate is expected. Certificate verification can
     * be controlled using the TLSSkipVerify.
     */
    public ?string $HTTP = null;

    public ?string $Method = null;

    public ?string $Body = null;
    /**
     * @var string[]
     */
    public ?array $Header = null;

    /**
     * Specifies a TCP to connect against the value of TCP (expected to be an IP or hostname plus port combination).
     */
    public ?string $TCP = null;

    /**
     * Specifies this is a TTL check, and the TTL endpoint must be used periodically to update the state of the check.
     */
    private ?int $TTL = null;
}
