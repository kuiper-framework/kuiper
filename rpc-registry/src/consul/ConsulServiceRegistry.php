<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

use kuiper\rpc\server\Service;
use kuiper\rpc\server\ServiceRegistry;

class ConsulServiceRegistry implements ServiceRegistry
{
    /**
     * @var ConsulAgent
     */
    private $consulAgent;

    /**
     * ConsulServiceRegistry constructor.
     *
     * @param ConsulAgent $consulAgent
     */
    public function __construct(ConsulAgent $consulAgent)
    {
        $this->consulAgent = $consulAgent;
    }

    public function register(Service $service): void
    {
        $this->consulAgent->registerService($this->createServiceRequest($service), true);
    }

    public function deregister(Service $service): void
    {
        $this->consulAgent->deregisterService($this->getServiceName($service));
    }

    private function createServiceRequest(Service $service): RegisterServiceRequest
    {
        $request = new RegisterServiceRequest();
        $request->Name = $this->getServiceName($service);
        $serverPort = $service->getServerPort();
        $request->Address = $serverPort->getHost();
        $request->Port = $serverPort->getPort();

        return $request;
    }

    private function getServiceName(Service $service): string
    {
        return $service->getServiceName();
    }
}
