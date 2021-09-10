<?php

declare(strict_types=1);

namespace kuiper\resilience;

use function DI\autowire;
use function DI\get;
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

class ResilienceConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            CounterFactory::class => autowire(SimpleCounterFactory::class),
            StateStore::class => autowire(SwooleTableStateStore::class),
            Clock::class => autowire(SimpleClock::class),
            MetricsFactory::class => autowire(MetricsFactoryImpl::class),
            CircuitBreakerFactory::class => autowire(CircuitBreakerFactoryImpl::class)
                ->constructorParameter('options', get('application.client.circuit_breaker')),
            RetryFactory::class => autowire(RetryFactoryImpl::class)
                ->constructorParameter('options', get('application.client.retry')),
        ];
    }
}
