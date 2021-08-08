<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

/**
 * @see https://www.consul.io/api/agent/check
 */
class ServiceCheck
{
    /**
     * @var string
     */
    public $Name;

    /**
     * @var string
     */
    public $ID;
    /**
     * @var string
     */
    public $DeregisterCriticalServiceAfter;
    /**
     * @var string[]
     */
    public $Args;
    /**
     * @var string
     */
    public $Interval;
    /**
     * @var string
     */
    public $Timeout;

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
     *
     * @var string
     */
    public $HTTP;

    /**
     * @var string
     */
    public $Method;
    /**
     * @var string
     */
    public $Body;
    /**
     * @var string[]
     */
    public $Header;
}
