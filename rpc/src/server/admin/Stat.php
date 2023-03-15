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

namespace kuiper\rpc\server\admin;

final class Stat
{
    /**
     * @var string
     */
    public readonly string $startTime;

    /**
     * @var int
     */
    public readonly int $connections;

    /**
     * @var int
     */
    public readonly int $acceptCount;

    /**
     * @var int
     */
    public readonly int $closeCount;

    /**
     * @var int
     */
    public readonly int $requestCount;

    /**
     * @var int
     */
    public readonly int $dispatchCount;

    /**
     * @var int
     */
    public readonly int $pendingTasks;

    public function __construct(
        string $startTime,
        int $connections,
        int $acceptCount,
        int $closeCount,
        int $requestCount,
        int $dispatchCount,
        int $pendingTasks
    ) {
        $this->startTime = $startTime;
        $this->connections = $connections;
        $this->acceptCount = $acceptCount;
        $this->closeCount = $closeCount;
        $this->requestCount = $requestCount;
        $this->dispatchCount = $dispatchCount;
        $this->pendingTasks = $pendingTasks;
    }
}
