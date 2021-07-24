<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\helper\Text;
use kuiper\swoole\constants\ClientSettings;
use Psr\Http\Message\UriInterface;

final class Endpoint
{
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
     * @var float|null
     */
    private $connectTimeout;

    /**
     * @var float|null
     */
    private $receiveTimeout;

    /**
     * Endpoint constructor.
     */
    public function __construct(string $protocol, string $host, int $port, ?float $connectTimeout, ?float $receiveTimeout)
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

    /**
     * @return float|null
     */
    public function getConnectTimeout(): ?float
    {
        return $this->connectTimeout;
    }

    /**
     * @return float|null
     */
    public function getReceiveTimeout(): ?float
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
                ClientSettings::CONNECT_TIMEOUT => $this->connectTimeout,
                ClientSettings::RECV_TIMEOUT => $this->receiveTimeout,
            ]))
        );
    }

    public static function fromString(string $uri): Endpoint
    {
        $parts = parse_url($uri);
        $options = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $options);
        }

        return new self(
            $parts['scheme'] ?? '',
            $parts['host'] ?? '',
            $parts['port'] ?? 0,
            self::filterTimeout($options[ClientSettings::CONNECT_TIMEOUT] ?? $options['timeout'] ?? null),
            self::filterTimeout($options[ClientSettings::RECV_TIMEOUT] ?? $options['timeout'] ?? null)
        );
    }

    private static function filterTimeout(?string $timeout): ?float
    {
        return Text::isNotEmpty($timeout) ? (float) $timeout : null;
    }

    /**
     * @param UriInterface $uri
     *
     * @return Endpoint
     */
    public static function fromUri(UriInterface $uri): Endpoint
    {
        parse_str($uri->getQuery(), $options);

        return new self(
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPort() ?? 0,
            self::filterTimeout($options[ClientSettings::CONNECT_TIMEOUT] ?? $options['timeout'] ?? null),
            self::filterTimeout($options[ClientSettings::RECV_TIMEOUT] ?? $options['timeout'] ?? null)
        );
    }
}
