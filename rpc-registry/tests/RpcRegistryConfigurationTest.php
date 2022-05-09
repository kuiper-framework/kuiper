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

    public function testServiceDiscovery(): void
    {
        $middleware = $this->getContainer()->get(ServiceDiscovery::class);
        // print_r($middleware);
        $this->assertInstanceOf(ServiceDiscovery::class, $middleware);
    }
}
