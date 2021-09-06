<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

interface ServiceRegistryInterface
{
    /**
     * @param string   $service
     * @param Endpoint $endpoint
     * @param int      $weight
     */
    public function register(string $service, Endpoint $endpoint, int $weight = ServiceEndpoint::DEFAULT_WEIGHT): void;

    /**
     * @param string   $service
     * @param Endpoint $endpoint
     */
    public function deregister(string $service, Endpoint $endpoint): void;
}
