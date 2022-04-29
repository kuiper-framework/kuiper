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
     * @var array
     */
    private array $settings;

    private ?int $socketType = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly ServerType $serverType,
        array $settings = [])
    {
        $this->settings = [];
        foreach ($settings as $name => $value) {
            if (ServerSetting::tryFrom($name) !== null) {
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

    public function getServerType(): ServerType
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
        return $this->serverType->isHttpProtocol();
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return array_merge($this->settings, $this->serverType->settings());
    }

    public function __toString(): string
    {
        return sprintf('%s://%s:%s', $this->serverType->value, $this->host, $this->port);
    }
}
