<?php

/** @noinspection PhpUnused */

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\tars\server;

use InvalidArgumentException;
use kuiper\rpc\transporter\Endpoint;
use kuiper\swoole\constants\ServerType;
use kuiper\tars\core\EndpointParser;
use Symfony\Component\Validator\Constraints as Assert;

class Adapter
{
    /**
     * @var array
     */
    private const PROTOCOL_ALIAS = [
        'not_tars' => 'http',
    ];

    #[Assert\NotBlank]
    private ?string $name = null;

    #[Assert\NotNull]
    private ?Endpoint $endpoint = null;

    private int $maxConns = 10000;

    #[Assert\Choice(callback: 'protocols')]
    #[Assert\NotBlank]
    private ?string $protocol = null;

    private int $queueCap = 50000;

    private int $queueTimeout = 20000;

    #[Assert\NotBlank]
    private ?string $servant = null;

    private int $threads = 1;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getEndpoint(): ?Endpoint
    {
        return $this->endpoint;
    }

    public function setEndpoint(Endpoint|string|null $endpoint): void
    {
        if (is_string($endpoint)) {
            $this->endpoint = EndpointParser::parse($endpoint);
        } else {
            $this->endpoint = $endpoint;
        }
    }

    public function getMaxConns(): int
    {
        return $this->maxConns;
    }

    public function setMaxConns(int $maxConns): void
    {
        $this->maxConns = $maxConns;
    }

    public function getQueueCap(): int
    {
        return $this->queueCap;
    }

    public function setQueueCap(int $queueCap): void
    {
        $this->queueCap = $queueCap;
    }

    public function getQueueTimeout(): int
    {
        return $this->queueTimeout;
    }

    public function setQueueTimeout(int $queueTimeout): void
    {
        $this->queueTimeout = $queueTimeout;
    }

    public function getServant(): ?string
    {
        return $this->servant;
    }

    public function setServant(?string $servant): void
    {
        $this->servant = $servant;
    }

    public function getThreads(): int
    {
        return $this->threads;
    }

    public function setThreads(int $threads): void
    {
        $this->threads = $threads;
    }

    public function getProtocol(): ?string
    {
        return $this->protocol;
    }

    public function setProtocol(string $protocol): void
    {
        $this->protocol = self::PROTOCOL_ALIAS[$protocol] ?? $protocol;
    }

    public function getAdapterName(): string
    {
        return $this->servant.'Adapter';
    }

    public function getServerType(): ServerType
    {
        $protocol = Protocol::from($this->protocol);
        if (null !== $protocol->getServerType()) {
            return $protocol->getServerType();
        }
        $serverType = ServerType::tryFrom($this->endpoint->getProtocol());
        if (null !== $serverType) {
            return $serverType;
        }
        throw new InvalidArgumentException('Cannot determine server type from protocol '.$this->protocol);
    }

    public function getSwooleSockType(): int
    {
        return ServerType::UDP === $this->getServerType() ? SWOOLE_SOCK_UDP : SWOOLE_SOCK_TCP;
    }

    /**
     * @return string[]
     */
    public function protocols(): array
    {
        return array_map(static function (Protocol $protocol) {
            return $protocol->value;
        }, Protocol::cases());
    }
}
