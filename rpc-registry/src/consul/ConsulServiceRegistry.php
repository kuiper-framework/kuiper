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

namespace kuiper\rpc\registry\consul;

use kuiper\rpc\server\Service;
use kuiper\rpc\server\ServiceRegistryInterface;
use kuiper\swoole\constants\ServerType;

class ConsulServiceRegistry implements ServiceRegistryInterface
{
    public function __construct(
        private readonly ConsulAgent $consulAgent,
        private readonly array $options = [])
    {
    }

    public function register(Service $service): void
    {
        $this->consulAgent->registerService($this->createServiceRequest($service), true);
    }

    public function deregister(Service $service): void
    {
        $this->consulAgent->deregisterService($this->getServiceId($service));
    }

    private function createServiceRequest(Service $service): RegisterServiceRequest
    {
        $serverPort = $service->getServerPort();
        $request = new RegisterServiceRequest();
        $request->Name = $this->getServiceName($service);
        $request->ID = $this->getServiceId($service);
        $request->Address = $serverPort->getHost();
        $request->Port = $serverPort->getPort();
        $serviceCheck = new RegisterServiceCheck();
        $serviceCheck->Interval = $this->options['healthy_check_interval'] ?? '5s';
        if ($serverPort->getServerType()->isHttpProtocol()) {
            $serviceCheck->HTTP = sprintf('http://%s:%d%s', $serverPort->getHost(), $serverPort->getPort(), $this->options['healthy_check_path'] ?? '/');
        } else {
            $serviceCheck->TCP = sprintf('%s:%d', $serverPort->getHost(), $serverPort->getPort());
        }
        $request->Check = $serviceCheck;

        return $request;
    }

    private function getServiceId(Service $service): string
    {
        $serverPort = $service->getServerPort();

        return sprintf('%s@%s:%d', $service->getServiceName(), $serverPort->getHost(), $serverPort->getPort());
    }

    private function getServiceName(Service $service): string
    {
        return $service->getServiceName();
    }
}
