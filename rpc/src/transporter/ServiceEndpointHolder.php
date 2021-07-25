<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\rpc\exception\CannotResolveEndpointException;

class ServiceEndpointHolder implements EndpointHolderInterface, Refreshable
{
    /**
     * @var ServiceResolverInterface
     */
    private $serviceRegistry;
    /**
     * @var string
     */
    private $service;
    /**
     * @var ServiceEndpoint
     */
    private $serviceEndpoint;

    /**
     * RouteHolder constructor.
     */
    public function __construct(ServiceResolverInterface $serviceRegistry, string $service)
    {
        $this->serviceRegistry = $serviceRegistry;
        $this->service = $service;
    }

    public function get(): Endpoint
    {
        if (!isset($this->serviceEndpoint)) {
            $this->serviceEndpoint = $this->serviceRegistry->resolve($this->service);
            if (null === $this->serviceEndpoint || $this->serviceEndpoint->isEmpty()) {
                throw new CannotResolveEndpointException("resolve {$this->service} fail");
            }
        }

        return $this->serviceEndpoint->current();
    }

    public function refresh(bool $force = false): void
    {
        if ($force) {
            $this->serviceEndpoint = null;

            return;
        }
        if ($this->serviceEndpoint->valid()) {
            $this->serviceEndpoint->next();
        } else {
            $this->serviceEndpoint->rewind();
        }
    }
}
