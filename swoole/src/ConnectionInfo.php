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

namespace kuiper\swoole;

class ConnectionInfo
{
    public function __construct(
        private readonly string $remoteIp,
        private readonly int $remotePort,
        private readonly int $serverPort,
        private readonly int $serverFd,
        private readonly int $connectTime,
        private readonly int $lastTime)
    {
    }

    /**
     * @return string
     */
    public function getRemoteIp(): string
    {
        return $this->remoteIp;
    }

    /**
     * @return int
     */
    public function getRemotePort(): int
    {
        return $this->remotePort;
    }

    /**
     * @return int
     */
    public function getServerPort(): int
    {
        return $this->serverPort;
    }

    /**
     * @return int
     */
    public function getServerFd(): int
    {
        return $this->serverFd;
    }

    /**
     * @return int
     */
    public function getConnectTime(): int
    {
        return $this->connectTime;
    }

    /**
     * @return int
     */
    public function getLastTime(): int
    {
        return $this->lastTime;
    }
}
