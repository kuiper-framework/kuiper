<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

use kuiper\http\client\annotation\DeleteMapping;
use kuiper\http\client\annotation\GetMapping;
use kuiper\http\client\annotation\HttpClient;
use kuiper\http\client\annotation\PutMapping;
use kuiper\http\client\annotation\RequestHeader;

/**
 * @HttpClient(client="consulHttpClient", path="/v1/agent")
 * @RequestHeader("content-type: json")
 */
interface ConsulAgent
{
    /**
     * @GetMapping("/services")
     *
     * @return Service[]
     */
    public function getServices(string $filter, ?string $ns = null): array;

    /**
     * @GetMapping("/service/{service}")
     *
     * @param string $service
     *
     * @return Service
     */
    public function getService(string $service): Service;

    /**
     * @PutMapping("/service/register")
     */
    public function registerService(RegisterServiceRequest $request): void;

    /**
     * @DeleteMapping("/service/deregister/{service}")
     */
    public function deregisterService(string $service): void;
}
