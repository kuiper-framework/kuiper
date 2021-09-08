<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

/**
 * Class ServiceHealthCheck.
 */
class ServiceHealthCheck
{
    /**
     * @var string
     */
    public $Node;

    /**
     * @var string
     */
    public $CheckID;
    /**
     * @var string
     */
    public $Name;
    /**
     * @var string
     */
    public $Status;
    /**
     * @var string
     */
    public $Notes;
    /**
     * @var string
     */
    public $Output;
    /**
     * @var string
     */
    public $ServiceID;
    /**
     * @var string
     */
    public $ServiceName;
    /**
     * @var string
     */
    public $Type;
    /**
     * @var string[]
     */
    public $ServiceTags;

    /**
     * @var int
     */
    public $CreateIndex;
}
