<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery;

use kuiper\rpc\server\Service;
use kuiper\rpc\server\ServiceRegistryInterface;
use kuiper\rpc\ServiceLocator;
use kuiper\rpc\transporter\Endpoint;

class InMemoryServiceResolver implements ServiceResolverInterface, ServiceRegistryInterface
{
    /**
     * @var ServiceEndpoint[]
     */
    private $serviceEndpoints;

    /**
     * {@inheritDoc}
     */
    public function register(Service $service): void
    {
        $key = (string) $service->getServiceLocator();
        $endpoint = $this->toEndpoint($service);

        if (isset($this->serviceEndpoints[$key])) {
            $this->serviceEndpoints[$key] = $this->serviceEndpoints[$key]->add($endpoint);
        } else {
            $this->serviceEndpoints[$key] = new ServiceEndpoint($service->getServiceLocator(), [$endpoint]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deregister(Service $service): void
    {
        $key = (string) $service->getServiceLocator();
        if (!isset($this->serviceEndpoints[$key])) {
            return;
        }
        $this->serviceEndpoints[$key] = $this->serviceEndpoints[$key]->remove($this->toEndpoint($service));
    }

    private function toEndpoint(Service $service): Endpoint
    {
        $serverPort = $service->getServerPort();

        return new Endpoint(
            $serverPort->getServerType(),
            $serverPort->getHost(),
            $serverPort->getPort(),
            null,
            null,
            ['weight' => $service->getWeight()]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(ServiceLocator $serviceLocator): ?ServiceEndpoint
    {
        return $this->serviceEndpoints[(string) $serviceLocator] ?? null;
    }

    /**
     * @param ServiceEndpoint[]|string[] $serviceEndpoints
     *
     * @return self
     */
    public static function create(array $serviceEndpoints): self
    {
        $registry = new self();
        foreach ($serviceEndpoints as $serviceEndpoint) {
            if (is_string($serviceEndpoint)) {
                $serviceEndpoint = ServiceEndpoint::fromString($serviceEndpoint);
            }
            $registry->serviceEndpoints[(string) $serviceEndpoint->getServiceLocator()] = $serviceEndpoint;
        }

        return $registry;
    }
}
