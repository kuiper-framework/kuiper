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
    /**
     * @var string
     */
    private $remoteIp;

    /**
     * @var int
     */
    private $remotePort;

    /**
     * @var int
     */
    private $serverPort;

    /**
     * @var int
     */
    private $serverFd;

    /**
     * @var int
     */
    private $connectTime;

    /**
     * @var int
     */
    private $lastTime;

    public function getRemoteIp(): string
    {
        return $this->remoteIp;
    }

    public function setRemoteIp(string $remoteIp): void
    {
        $this->remoteIp = $remoteIp;
    }

    public function getRemotePort(): int
    {
        return $this->remotePort;
    }

    public function setRemotePort(int $remotePort): void
    {
        $this->remotePort = $remotePort;
    }

    public function getServerPort(): int
    {
        return $this->serverPort;
    }

    public function setServerPort(int $serverPort): void
    {
        $this->serverPort = $serverPort;
    }

    public function getServerFd(): int
    {
        return $this->serverFd;
    }

    public function setServerFd(int $serverFd): void
    {
        $this->serverFd = $serverFd;
    }

    public function getConnectTime(): int
    {
        return $this->connectTime;
    }

    public function setConnectTime(int $connectTime): void
    {
        $this->connectTime = $connectTime;
    }

    public function getLastTime(): int
    {
        return $this->lastTime;
    }

    public function setLastTime(int $lastTime): void
    {
        $this->lastTime = $lastTime;
    }
}
