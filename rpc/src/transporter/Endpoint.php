<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use Psr\Http\Message\UriInterface;

class Endpoint
{
    public const CONNECT_TIMEOUT = 'connect_timeout';
    public const RECV_TIMEOUT = 'recv_timeout';

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $port;

    /**
     * @var int|null
     */
    private $connectTimeout;

    /**
     * @var int|null
     */
    private $receiveTimeout;

    /**
     * Endpoint constructor.
     */
    public function __construct(string $protocol, string $host, int $port, ?int $connectTimeout, ?int $receiveTimeout)
    {
        $this->protocol = $protocol;
        $this->host = $host;
        $this->port = $port;
        $this->connectTimeout = $connectTimeout;
        $this->receiveTimeout = $receiveTimeout;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    public function getReceiveTimeout(): int
    {
        return $this->receiveTimeout;
    }

    public function equals(Endpoint $other): bool
    {
        return $this->getHost() === $other->getHost()
            && $this->getPort() === $other->getPort();
    }

    public function __toString(): string
    {
        return sprintf('%s://%s:%d?%s',
            $this->protocol,
            $this->host,
            $this->port,
            http_build_query(array_filter([
                self::CONNECT_TIMEOUT => $this->connectTimeout,
                self::RECV_TIMEOUT => $this->receiveTimeout,
            ]))
        );
    }

    public static function fromUri(UriInterface $uri): self
    {
        parse_str($uri->getQuery(), $options);

        return new self(
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPort() ?? 0,
            (int) ($options[self::CONNECT_TIMEOUT] ?? $options['timeout'] ?? null),
            (int) ($options[self::RECV_TIMEOUT] ?? $options['timeout'] ?? null)
        );
    }
}
