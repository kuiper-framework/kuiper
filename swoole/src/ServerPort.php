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

use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\constants\ServerType;

class ServerPort
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     *
     * @see ServerType
     */
    private $serverType;

    /**
     * @var int
     */
    private $socketType;

    /**
     * @var array
     */
    private $settings;

    public function __construct(string $host, int $port, string $serverType, array $settings = [])
    {
        $this->host = $host;
        $this->port = $port;
        if (!ServerType::hasValue($serverType)) {
            throw new \InvalidArgumentException("Unknown server type $serverType");
        }
        $this->serverType = $serverType;
        $this->settings = [];
        foreach ($settings as $name => $value) {
            if (ServerSetting::hasValue($name)) {
                $this->settings[$name] = $value;
            }
        }
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getServerType(): string
    {
        return $this->serverType;
    }

    public function setSocketType(int $socketType): void
    {
        $this->socketType = $socketType;
    }

    public function getSockType(): int
    {
        return $this->socketType
            ?? (ServerType::UDP === $this->serverType ? SWOOLE_SOCK_UDP : SWOOLE_SOCK_TCP);
    }

    public function isHttpProtocol(): bool
    {
        return ServerType::fromValue($this->serverType)->isHttpProtocol();
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return array_merge($this->settings, ServerType::fromValue($this->serverType)->settings);
    }

    public function __toString(): string
    {
        return sprintf('%s://%s:%s', $this->serverType, $this->host, $this->port);
    }
}
