<?php

declare(strict_types=1);

namespace kuiper\swoole;

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
     * @var ServerType
     */
    private $serverType;

    /**
     * @var int
     */
    private $socketType;

    public function __construct(string $host, int $port, ServerType $serverType)
    {
        $this->host = $host;
        $this->port = $port;
        $this->serverType = $serverType;
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

    public function setSocketType(int $socketType): self
    {
        $this->socketType = $socketType;

        return $this;
    }

    public function getSockType(): int
    {
        return $this->socketType
            ?? (ServerType::UDP === $this->serverType->value ? SWOOLE_SOCK_UDP : SWOOLE_SOCK_TCP);
    }

    public function __toString(): string
    {
        return sprintf('%s://%s:%s', $this->serverType, $this->host, $this->port);
    }
}
