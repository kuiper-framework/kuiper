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
     * @var array
     */
    private $options;

    /**
     * Endpoint constructor.
     */
    public function __construct(string $protocol, string $host, int $port, ?float $connectTimeout, ?float $receiveTimeout, array $options = [])
    {
        $this->protocol = $protocol;
        $this->host = $host;
        $this->port = $port;
        $this->connectTimeout = $connectTimeout;
        $this->receiveTimeout = $receiveTimeout;
        $this->options = $options;
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

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption(string $name)
    {
        return $this->options[$name];
    }

    public function getAddress(): string
    {
        return $this->host.':'.$this->port;
    }

    public function equals(Endpoint $other): bool
    {
        return $this->getProtocol() === $other->getProtocol()
            && $this->getHost() === $other->getHost()
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

    public static function fromAddress(string $address): self
    {
        if (false === strpos($address, ':')) {
            throw new \InvalidArgumentException("invalid server address '$address'");
        }
        [$host, $port] = explode(':', $address);

        return new self('tcp', $host, (int) $port, null, null);
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
            self::filterTimeout($options[ClientSettings::RECV_TIMEOUT] ?? $options['timeout'] ?? null),
            $options
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
            self::filterTimeout($options[ClientSettings::RECV_TIMEOUT] ?? $options['timeout'] ?? null),
            $options
        );
    }
}
