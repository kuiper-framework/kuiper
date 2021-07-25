<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

class ServiceEndpoint implements \Iterator
{
    public const DEFAULT_WEIGHT = 100;
    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var array
     */
    private $endpoints;

    /**
     * @var int[]
     */
    private $weights;

    /**
     * ServiceEndpoint constructor.
     *
     * @param string     $serviceName
     * @param Endpoint[] $endpoints
     * @param int[]      $weights
     */
    public function __construct(string $serviceName, array $endpoints, array $weights)
    {
        $this->serviceName = $serviceName;
        foreach ($endpoints as $i => $endpoint) {
            $this->register($endpoint, $weights[$i] ?? $weights[$endpoint->getAddress()] ?? self::DEFAULT_WEIGHT);
        }
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
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

    public function getWeight(string $address): ?int
    {
        return $this->weights[$address] ?? null;
    }

    public function register(Endpoint $endpoint, int $weight = self::DEFAULT_WEIGHT): void
    {
        $address = $endpoint->getAddress();
        $this->endpoints[$address] = $endpoint;
        $this->weights[$address] = $weight;
    }

    public function unregister(Endpoint $endpoint): void
    {
        $address = $endpoint->getAddress();
        unset($this->endpoints[$address], $this->weights[$address]);
    }

    /**
     * {@inheritDoc}
     */
    public function current()
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
    public function key()
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
