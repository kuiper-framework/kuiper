<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\tars\core\EndpointParser;

class InMemoryServiceRegistry implements ServiceRegistryInterface, ServiceResolverInterface
{
    /**
     * @var ServiceEndpoint[]
     */
    private $serviceEndpoints;

    /**
     * {@inheritDoc}
     */
    public function register(string $service, Endpoint $endpoint, int $weight = ServiceEndpoint::DEFAULT_WEIGHT): void
    {
        if (!isset($this->serviceEndpoints[$service])) {
            $this->serviceEndpoints[$service] = new ServiceEndpoint($service, [], []);
        }
        $this->serviceEndpoints[$service]->register($endpoint, $weight);
    }

    public function registerService(ServiceEndpoint $serviceEndpoint): void
    {
        foreach ($serviceEndpoint->getEndpoints() as $endpoint) {
            $this->register($serviceEndpoint->getServiceName(), $endpoint, $serviceEndpoint->getWeight($endpoint->getAddress()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unregister(string $service, Endpoint $endpoint): void
    {
        if (!isset($this->serviceEndpoints[$service])) {
            return;
        }
        $this->serviceEndpoints[$service]->unregister($endpoint);
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(string $service): ?ServiceEndpoint
    {
        return $this->serviceEndpoints[$service] ?? null;
    }

    public static function create(array $serviceEndpoints): self
    {
        $registry = new self();
        foreach ($serviceEndpoints as $serviceEndpoint) {
            if (is_string($serviceEndpoint)) {
                $serviceEndpoint = EndpointParser::parseServiceEndpoint($serviceEndpoint);
            }
            $registry->registerService($serviceEndpoint);
        }

        return $registry;
    }
}