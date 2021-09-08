<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

use kuiper\rpc\servicediscovery\ServiceEndpoint;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\rpc\ServiceLocator;
use kuiper\rpc\transporter\Endpoint;

class ConsulServiceResolver implements ServiceResolverInterface
{
    /**
     * @var ConsulAgent
     */
    private $consulAgent;

    /**
     * ConsulServiceResolver constructor.
     *
     * @param ConsulAgent $consulAgent
     */
    public function __construct(ConsulAgent $consulAgent)
    {
        $this->consulAgent = $consulAgent;
    }

    public function resolve(ServiceLocator $serviceLocator): ?ServiceEndpoint
    {
        $services = $this->consulAgent->getServiceHealth($serviceLocator->getName());
        if (empty($services)) {
            return null;
        }
        $endpoints = [];
        foreach ($services as $service) {
            if ('passing' === $service->AggregatedStatus) {
                $service = $service->Service;
                $endpoints[] = new Endpoint('', $service->Address, $service->Port, null, null, []);
            }
        }
        if (empty($endpoints)) {
            return null;
        }

        return new ServiceEndpoint($serviceLocator, $endpoints);
    }
}
