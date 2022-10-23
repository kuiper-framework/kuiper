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

namespace kuiper\rpc\client\middleware;

use GuzzleHttp\Psr7\Response;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\event\InMemoryEventDispatcher;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use kuiper\resilience\circuitbreaker\CircuitBreakerFactory;
use kuiper\resilience\circuitbreaker\exception\CallNotPermittedException;
use kuiper\resilience\ResilienceConfiguration;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\client\RpcExecutorFactory;
use kuiper\rpc\fixtures\HelloService;
use kuiper\rpc\fixtures\RpcRequestFactory;
use kuiper\rpc\fixtures\RpcResponseFactory;
use kuiper\rpc\registry\TestCase;
use kuiper\rpc\transporter\SimpleSession;
use kuiper\rpc\transporter\TransporterInterface;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestInterface;

class CircuitBreakerMiddlewareTest extends TestCase
{
    public function testName(): void
    {
        $proxyGenerator = new ProxyGenerator();
        $class = $proxyGenerator->generate(HelloService::class);
        $class->eval();
        $className = $class->getClassName();
        $transporter = \Mockery::mock(TransporterInterface::class);
        $transporter->shouldReceive('createSession')
            ->andReturnUsing(function (RequestInterface $req) use (&$c) {
                static $c = [];
                error_log('send request');
                $args = json_decode((string) $req->getBody(), true);
                $count = $c[$args[0]] ?? 0;
                $c[$args[0]] = $count + 1;
                if ($args[0] % 2 && $count < 2) {
                    throw new \InvalidArgumentException("invalid arg $args[0]");
                }

                return new SimpleSession(new Response());
            });
        $responseFactory = new RpcResponseFactory();
        $rpcClient = new RpcClient($transporter, $responseFactory);
        $executorFactory = new RpcExecutorFactory(new RpcRequestFactory(), $rpcClient);
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new ResilienceConfiguration());
        $config = Properties::create([
            'application' => [
                'client' => [
                    'circuitbreaker' => [
                        'default' => [
                        ],
                        HelloService::class => [
                            'minimum_number_of_calls' => 2,
                        ],
                    ],
                ],
            ],
        ]);
        $builder->addDefinitions(new PropertiesDefinitionSource($config));
        $builder->addDefinitions([
            EventDispatcherInterface::class => $eventDispatcher = new InMemoryEventDispatcher(),
            PropertyResolverInterface::class => $config,
            PoolFactoryInterface::class => new PoolFactory(),
        ]);
        $container = $builder->build();
        $executorFactory->setContainer($container);

        /** @var HelloService $service */
        $service = new $className($executorFactory);
        $responseFactory->setResult(['hello world']);
        $ret = null;
        foreach (range(1, 3) as $times) {
            try {
                $ret = $service->hello((string) $times);
            } catch (CallNotPermittedException $e) {
                break;
            }
        }
        $events = $eventDispatcher->getEvents();
        // echo count($events);
        $this->assertEquals('hello world', $ret);
        // $this->assertCount(2, $events);
        /** @var \kuiper\resilience\circuitbreaker\CircuitBreaker $circuitBreaker */
        $circuitBreaker = array_values($container->get(CircuitBreakerFactory::class)->getCircuitBreakerList())[0];
        $metrics = $circuitBreaker->getMetrics();
        var_export([
            $metrics->getFailureRate(),
            $metrics->getSlowCallRate(),
        ]);
        // $this->assertEquals(3, $retry->getMetrics());
    }
}
