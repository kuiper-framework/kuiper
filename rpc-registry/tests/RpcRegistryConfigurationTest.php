<?php

declare(strict_types=1);

namespace kuiper\rpc\registry;

use kuiper\rpc\client\middleware\ServiceDiscovery;


class RpcRegistryConfigurationTest extends TestCase
{
    protected function getConfig(): array
    {
        return [
            'application' => [
                'client' => [
                    'service_discovery' => [
                       // 'load_balance' => 'random'
                    ],
                ],
            ],
        ];
    }

    public function testServiceDiscovery()
    {
        $middleware = $this->getContainer()->get(ServiceDiscovery::class);
        print_r($middleware);
    }
}
