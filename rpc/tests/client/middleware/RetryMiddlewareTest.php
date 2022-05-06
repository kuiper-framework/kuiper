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
use kuiper\resilience\circuitbreaker\exception\CallNotPermittedException;
use kuiper\resilience\ResilienceConfiguration;
use kuiper\resilience\retry\RetryFactory;
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

class RetryMiddlewareTest extends TestCase
{
    public function testName(): void
    {
        $proxyGenerator = new ProxyGenerator();
        $class = $proxyGenerator->generate(HelloService::class);
        $class->eval();
        $className = $class->getClassName();
        $transporter = \Mockery::mock(TransporterInterface::class);
        $c = 1;
        $transporter->shouldReceive('createSession')
            ->andReturnUsing(function ($req) use (&$c) {
                error_log('send request');
                if ($c++ < 2) {
                    throw new \InvalidArgumentException('fail');
                }

                return new SimpleSession(new Response());
            });
        $responseFactory = new RpcResponseFactory();
        $rpcClient = new RpcClient($transporter, $responseFactory);
        $executorFactory = new RpcExecutorFactory(new RpcRequestFactory(), $rpcClient);
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new ResilienceConfiguration());
        //$ignoreErrors = [\InvalidArgumentException::class];
        $ignoreErrors = [];
        $config = Properties::create([
            'application' => [
                'client' => [
                    'retry' => [
                        HelloService::class => [
                            'wait_duration' => 0,
                            'ignore_exceptions' => $ignoreErrors,
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
        $ret = [];
        foreach (range(1, 3) as $times) {
            // $c = 1;
            try {
                $ret[$times] = $service->hello('world');
            } catch (\InvalidArgumentException $e) {
            }
        }
        $events = $eventDispatcher->getEvents();
        // echo count($events);
        // var_export($ret);
        // $this->assertEquals('hello world', $ret);
        // $this->assertCount(2, $events);
        /** @var \kuiper\resilience\retry\Retry $retry */
        $retryList = $container->get(RetryFactory::class)->getRetryList();
        $retry = array_values($retryList)[0];
        $this->assertEquals(1, $retry->getMetrics()->getNumberOfSuccessfulCallsWithRetryAttempt());
    }
}
