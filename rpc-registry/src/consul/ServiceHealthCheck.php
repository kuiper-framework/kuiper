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
 * Class ServiceHealthCheck.
 */
class ServiceHealthCheck
{
    public ?string $Node = null;

    public ?string $CheckID = null;

    public ?string $Name = null;

    public ?string $Status = null;

    public ?string $Notes = null;

    public ?string $Output = null;

    public ?string $ServiceID = null;

    public ?string $ServiceName = null;

    public ?string $Type = null;
    /**
     * @var string[]
     */
    public ?array $ServiceTags = null;

    public ?int $CreateIndex = null;
}
