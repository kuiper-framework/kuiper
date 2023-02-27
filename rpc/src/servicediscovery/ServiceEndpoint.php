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

namespace kuiper\rpc\servicediscovery;

use InvalidArgumentException;
use Iterator;
use kuiper\rpc\ServiceLocator;
use kuiper\rpc\ServiceLocatorImpl;
use kuiper\rpc\transporter\Endpoint;

class ServiceEndpoint implements Iterator
{
    /**
     * @var Endpoint[]
     */
    private array $endpoints;

    public function __construct(private readonly ServiceLocator $serviceLocator, array $endpoints)
    {
        foreach ($endpoints as $endpoint) {
            $this->endpoints[$endpoint->getAddress()] = $endpoint;
        }
    }

    /**
     * service@endpoint.
     */
    public static function fromString(string $serviceEndpoint): self
    {
        $pos = strpos($serviceEndpoint, '@');
        if (false === $pos) {
            throw new InvalidArgumentException("invalid service endpoint '$serviceEndpoint'");
        }

        return new self(
            ServiceLocatorImpl::fromString(substr($serviceEndpoint, 0, $pos)),
            array_map([Endpoint::class, 'fromString'], explode(';', substr($serviceEndpoint, $pos + 1)))
        );
    }

    public function __toString()
    {
        return $this->serviceLocator.'@'.implode(';', $this->endpoints);
    }

    /**
     * @return ServiceLocator
     */
    public function getServiceLocator(): ServiceLocator
    {
        return $this->serviceLocator;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceLocator->getName();
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->serviceLocator->getNamespace();
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->serviceLocator->getVersion();
    }

    public function isEmpty(): bool
    {
        return empty($this->endpoints);
    }

    /**
     * @return Endpoint[]
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    public function getEndpoint(string $address): ?Endpoint
    {
        return $this->endpoints[$address] ?? null;
    }

    public function add(Endpoint $endpoint): self
    {
        $endpoints = $this->endpoints;
        $endpoints[$endpoint->getAddress()] = $endpoint;

        return new self($this->serviceLocator, $endpoints);
    }

    public function remove(Endpoint $endpoint): self
    {
        $address = $endpoint->getAddress();
        if (isset($this->endpoints[$address])) {
            $endpoints = $this->endpoints;
            unset($endpoints[$address]);

            return new self($this->serviceLocator, $endpoints);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return mixed
     */
    public function current(): mixed
    {
        return current($this->endpoints);
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        next($this->endpoints);
    }

    /**
     * {@inheritDoc}
     */
    public function key(): mixed
    {
        return key($this->endpoints);
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return null !== $this->key();
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        reset($this->endpoints);
    }
}
