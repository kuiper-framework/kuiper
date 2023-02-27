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

namespace kuiper\rpc\transporter;

use InvalidArgumentException;
use kuiper\helper\Text;
use kuiper\swoole\constants\ClientSettings;
use Psr\Http\Message\UriInterface;

final class Endpoint
{
    /**
     * Endpoint constructor.
     */
    public function __construct(
        private readonly string $protocol,
        private readonly string $host,
        private readonly int $port,
        private readonly ?float $connectTimeout,
        private readonly ?float $receiveTimeout,
        private readonly array $options = [])
    {
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
        return $this->options[$name] ?? null;
    }

    public function getAddress(): string
    {
        return $this->host.':'.$this->port;
    }

    public function equals(Endpoint $other): bool
    {
        return $this->protocol === $other->protocol
            && $this->host === $other->host
            && $this->port === $other->port
            && $this->connectTimeout === $other->connectTimeout
            && $this->receiveTimeout === $other->receiveTimeout
            && $this->options === $other->options;
    }

    public function __toString(): string
    {
        return sprintf('%s://%s:%d?%s',
            $this->protocol,
            $this->host,
            $this->port,
            http_build_query(array_merge($this->options, array_filter([
                ClientSettings::CONNECT_TIMEOUT => $this->connectTimeout,
                ClientSettings::RECV_TIMEOUT => $this->receiveTimeout,
            ])))
        );
    }

    public static function fromAddress(string $address): self
    {
        if (!str_contains($address, ':')) {
            throw new InvalidArgumentException("invalid server address '$address'");
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

        return self::create($parts['scheme'] ?? '', $parts['host'] ?? '', $parts['port'] ?? 0, $options);
    }

    /**
     * @param UriInterface $uri
     *
     * @return Endpoint
     */
    public static function fromUri(UriInterface $uri): Endpoint
    {
        parse_str($uri->getQuery(), $options);

        return self::create($uri->getScheme(), $uri->getHost(), $uri->getPort() ?? 0, $options);
    }

    public static function removeTcpScheme(string $uri): string
    {
        return preg_replace('#^tcp://#', '//', $uri);
    }

    private static function create(string $schema, string $host, int $port, array $options): self
    {
        $connectTimeout = self::filterTimeout($options[ClientSettings::CONNECT_TIMEOUT] ?? $options['timeout'] ?? null);
        $receiveTimeout = self::filterTimeout($options[ClientSettings::RECV_TIMEOUT] ?? $options['timeout'] ?? null);
        unset($options[ClientSettings::CONNECT_TIMEOUT], $options[ClientSettings::RECV_TIMEOUT], $options['timeout']);

        return new self(
            $schema,
            $host,
            $port,
            $connectTimeout,
            $receiveTimeout,
            $options
        );
    }

    private static function filterTimeout(?string $timeout): ?float
    {
        return Text::isNotEmpty($timeout) ? (float) $timeout : null;
    }

    public function merge(Endpoint $endpoint): Endpoint
    {
        return new self(
            '' !== $this->protocol ? $this->protocol : $endpoint->protocol,
            '' !== $this->host ? $this->host : $endpoint->host,
            $this->port > 0 ? $this->port : $endpoint->port,
            $this->connectTimeout ?? $endpoint->connectTimeout,
            $this->receiveTimeout ?? $endpoint->receiveTimeout,
            array_merge($endpoint->options, $this->options)
        );
    }
}
