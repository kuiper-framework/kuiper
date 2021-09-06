<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

use kuiper\http\client\annotation\GetMapping;
use kuiper\http\client\annotation\HttpClient;
use kuiper\http\client\annotation\PutMapping;
use kuiper\http\client\annotation\RequestHeader;

/**
 * @HttpClient(client="consulHttpClient", path="/v1/agent")
 * @RequestHeader("content-type: application/json")
 */
interface ConsulAgent
{
    /**
     * @GetMapping("/services")
     *
     * @return Service[]
     */
    public function getServices(string $filter = null, string $ns = null): array;

    /**
     * @GetMapping("/service/{service}")
     *
     * @param string $service
     *
     * @return Service
     */
    public function getService(string $service): Service;

    /**
     * @PutMapping("/service/register", queryParams={"replace-existing-checks": "replaceExistingChecks"})
     */
    public function registerService(RegisterServiceRequest $request, bool $replaceExistingChecks = null): void;

    /**
     * @PutMapping("/service/deregister/{service}")
     */
    public function deregisterService(string $service): void;
}
