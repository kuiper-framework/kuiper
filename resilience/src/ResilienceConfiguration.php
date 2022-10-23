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

namespace kuiper\resilience;

use function DI\autowire;
use function DI\factory;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\resilience\circuitbreaker\CircuitBreakerFactory;
use kuiper\resilience\circuitbreaker\CircuitBreakerFactoryImpl;
use kuiper\resilience\circuitbreaker\StateStore;
use kuiper\resilience\circuitbreaker\SwooleTableStateStore;
use kuiper\resilience\core\Clock;
use kuiper\resilience\core\CounterFactory;
use kuiper\resilience\core\MetricsFactory;
use kuiper\resilience\core\MetricsFactoryImpl;
use kuiper\resilience\core\SimpleClock;
use kuiper\resilience\core\SimpleCounterFactory;
use kuiper\resilience\retry\RetryFactory;
use kuiper\resilience\retry\RetryFactoryImpl;
use kuiper\rpc\exception\ServerException;
use kuiper\swoole\Application;
use Psr\Container\ContainerInterface;

class ResilienceConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        if (class_exists(Application::class)) {
            $ignoreExceptions = [
                ServerException::class,
                \InvalidArgumentException::class,
            ];
            Application::getInstance()->getConfig()->merge([
                'application' => [
                    'client' => [
                        'retry' => [
                            'default' => [
                                'ignore_exceptions' => $ignoreExceptions,
                            ],
                        ],
                        'circuitbreaker' => [
                            'default' => [
                                'ignore_exceptions' => $ignoreExceptions,
                            ],
                        ],
                    ],
                ],
            ]);
        }

        return [
            CounterFactory::class => autowire(SimpleCounterFactory::class),
            StateStore::class => autowire(SwooleTableStateStore::class),
            Clock::class => autowire(SimpleClock::class),
            MetricsFactory::class => autowire(MetricsFactoryImpl::class),
            CircuitBreakerFactory::class => autowire(CircuitBreakerFactoryImpl::class)
                ->constructorParameter('options', factory(function (ContainerInterface $container) {
                    return $container->get('application.client.circuitbreaker') ?? [];
                })),
            RetryFactory::class => autowire(RetryFactoryImpl::class)
                ->constructorParameter('options', factory(function (ContainerInterface $container) {
                    return $container->get('application.client.retry') ?? [];
                })),
        ];
    }
}
