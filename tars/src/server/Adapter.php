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

namespace kuiper\tars\server;

use kuiper\rpc\transporter\Endpoint;
use kuiper\swoole\constants\ServerType;
use kuiper\tars\core\EndpointParser;
use Symfony\Component\Validator\Constraints as Assert;

class Adapter
{
    /**
     * @var array
     */
    private static $PROTOCOL_ALIAS = [
        'not_tars' => Protocol::HTTP,
    ];

    /**
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $name;

    /**
     * @Assert\NotNull()
     *
     * @var Endpoint|null
     */
    private $endpoint;
    /**
     * @var int
     */
    private $maxConns = 10000;
    /**
     * @Assert\Choice(callback="protocols")
     * @Assert\NotBlank()
     *
     * @var string|null
     *
     * @see Protocol
     */
    private $protocol;
    /**
     * @var int
     */
    private $queueCap = 50000;
    /**
     * @var int
     */
    private $queueTimeout = 20000;
    /**
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $servant;
    /**
     * @var int
     */
    private $threads = 1;

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

    /**
     * @param Endpoint|string|null $endpoint
     */
    public function setEndpoint($endpoint): void
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
        if (isset(self::$PROTOCOL_ALIAS[$protocol])) {
            $protocol = self::$PROTOCOL_ALIAS[$protocol];
        }
        $this->protocol = $protocol;
    }

    public function getAdapterName(): string
    {
        return $this->servant.'Adapter';
    }

    public function getServerType(): string
    {
        $protocol = Protocol::fromValue($this->protocol);
        if (null !== $protocol->serverType) {
            return $protocol->serverType;
        }
        if (ServerType::hasValue($this->endpoint->getProtocol())) {
            return $this->endpoint->getProtocol();
        }
        throw new \InvalidArgumentException('Cannot determine server type from protocol '.$this->protocol);
    }

    public function getSwooleSockType(): int
    {
        return ServerType::UDP === $this->getServerType() ? SWOOLE_SOCK_UDP : SWOOLE_SOCK_TCP;
    }

    public function protocols(): array
    {
        return Protocol::values();
    }
}
