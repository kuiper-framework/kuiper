<?php

namespace kuiper\boot\providers;

use kuiper\boot\Application;
use kuiper\boot\Events;
use kuiper\boot\TestCase;
use kuiper\rpc\client\Client;
use kuiper\rpc\client\HttpHandler;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class RpcClientProviderTest extends TestCase
{
    public function testConfigV1()
    {
        $app = new Application();
        $app->useAnnotations();
        $app->getSettings()->merge([
            'app' => [
                'providers' => [
                    RpcClientProvider::class,
                ],
                'rpc' => [
                    'source' => 'demo',
                    'servers' => [
                        CacheItemInterface::class => 'http://localhost/?module=cache',
                    ],
                    'services' => [
                        CacheItemInterface::class,
                    ],
                ],
            ],
        ]);
        $app->bootstrap();
        $app->getEventDispatcher()->addListener(Events::BOOT_RPC_CLIENT, function (GenericEvent $event) {
            /** @var Client $client */
            $client = $event->getSubject();
            $this->assertStringStartsWith('http://localhost/?module=cache&source=demo&host=', $client->getHandler()->getUri());
        });

        $object = $app->get(CacheItemInterface::class);
        $this->assertInstanceOf(CacheItemInterface::class, $object);
    }

    public function testConfigV2()
    {
        $app = new Application();
        $app->useAnnotations();
        $app->getSettings()->merge([
            'app' => [
                'providers' => [
                    RpcClientProvider::class,
                ],
                'rpc' => [
                    'source' => 'demo',
                    'servers' => [
                        'cache' => 'http://localhost/?module=cache',
                    ],
                    'services' => [
                        'cache' => [
                            CacheItemInterface::class,
                        ],
                    ],
                ],
            ],
        ]);
        $app->bootstrap();
        $app->getEventDispatcher()->addListener(Events::BOOT_RPC_CLIENT, function (GenericEvent $event) {
            /** @var Client $client */
            $client = $event->getSubject();
            $this->assertStringStartsWith('http://localhost/?module=cache&source=demo&host=', $client->getHandler()->getUri());
        });

        $object = $app->get(CacheItemInterface::class);
        $this->assertInstanceOf(CacheItemInterface::class, $object);
    }

    public function testConfigV3()
    {
        $app = new Application();
        $app->useAnnotations();
        $app->getSettings()->merge([
            'app' => [
                'providers' => [
                    RpcClientProvider::class,
                ],
                'rpc' => [
                    'source' => 'demo',
                    'providers' => [
                        'cache' => [
                            'timeout' => 20,
                            'server' => 'http://localhost/?module=cache',
                            'services' => [
                                CacheItemInterface::class,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $app->bootstrap();
        $app->getEventDispatcher()->addListener(Events::BOOT_RPC_CLIENT, function (GenericEvent $event) {
            /** @var Client $client */
            $client = $event->getSubject();
            /** @var HttpHandler $handler */
            $handler = $client->getHandler();
            $this->assertStringStartsWith('http://localhost/?module=cache&source=demo&host=', $handler->getUri());
            $this->assertEquals(20, $handler->getHttpClient()->getConfig('timeout'));
        });

        $object = $app->get(CacheItemInterface::class);
        $this->assertInstanceOf(CacheItemInterface::class, $object);
    }
}
