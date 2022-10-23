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

namespace kuiper\resilience\circuitbreaker;

use kuiper\event\InMemoryEventDispatcher;
use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnError;
use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnFailureRateExceeded;
use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnStateTransition;
use kuiper\resilience\circuitbreaker\exception\CallNotPermittedException;
use kuiper\resilience\core\MetricsFactoryImpl;
use kuiper\resilience\core\MockClock;
use kuiper\resilience\core\SimpleCounterFactory;
use kuiper\resilience\core\TryCall;
use PHPUnit\Framework\TestCase;

class CircuitBreakerImplTest extends TestCase
{
    private InMemoryEventDispatcher $eventDispatcher;

    private CircuitBreaker $circuitBreaker;

    private CircuitBreakerConfig $config;

    private MockClock $clock;

    protected function setUp(): void
    {
        $this->eventDispatcher = new InMemoryEventDispatcher();
        $this->clock = new MockClock();
        $this->config = CircuitBreakerConfig::builder()
            ->setSlidingWindowSize(4)
            ->setMinimumNumberOfCalls(2)
            ->setPermittedNumberOfCallsInHalfOpenState(2)
            ->build();
        $counterFactory = new SimpleCounterFactory();
        $metricFactory = new MetricsFactoryImpl($this->clock, $counterFactory);
        $this->circuitBreaker = new CircuitBreakerImpl('test', $this->config, $this->clock, new SwooleTableStateStore(), $counterFactory, $metricFactory);
        $this->circuitBreaker->setEventDispatcher($this->eventDispatcher);
    }

    public function testSuccess(): void
    {
        $call = static function () {
        };
        $this->circuitBreaker->call($call);
        $metrics = $this->circuitBreaker->getMetrics();
        // print_r($metrics);
        $this->assertEquals(1, $metrics->getNumberOfSuccessfulCalls());
        $this->assertEquals(-1, $metrics->getFailureRate());
    }

    public function testShouldTransitToOpenState(): void
    {
        $call = static function () {
            throw new \RuntimeException('error');
        };
        $result = [];
        foreach (range(1, 3) as $i) {
            $result[] = TryCall::call([$this->circuitBreaker, 'call'], $call);
        }
        $this->assertCount(3, array_filter($result, static function (TryCall $call) {
            return $call->isFailure();
        }));
        $this->assertInstanceOf(CallNotPermittedException::class, $result[2]->getException());
        $events = $this->eventDispatcher->getEvents();
        $this->assertEquals([
            CircuitBreakerOnError::class,
            CircuitBreakerOnError::class,
            CircuitBreakerOnFailureRateExceeded::class,
            CircuitBreakerOnStateTransition::class,
        ], array_map('get_class', $events));
        $metrics = $this->circuitBreaker->getMetrics();
        // print_r($metrics);
        $this->assertEquals(0, $metrics->getNumberOfSuccessfulCalls());
        $this->assertEquals(2, $metrics->getNumberOfFailedCalls());
        $this->assertEquals(1, $metrics->getNumberOfNotPermittedCalls());
        $this->assertEquals(100, $metrics->getFailureRate());
        $this->assertEquals(State::OPEN, $this->circuitBreaker->getState());
    }

    public function testShouldTransitToHalfOpen(): void
    {
        $counterFactory = new SimpleCounterFactory();
        $metricFactory = new MetricsFactoryImpl($this->clock, $counterFactory);
        $stateStore = new SwooleTableStateStore();
        $stateStore->setState('test', State::OPEN);
        $this->circuitBreaker = new CircuitBreakerImpl('test', $this->config, $this->clock, $stateStore, $counterFactory, $metricFactory);
        $this->circuitBreaker->setEventDispatcher($this->eventDispatcher);
        $this->assertEquals(State::OPEN, $this->circuitBreaker->getState());
        $this->clock->tick($this->config->getWaitIntervalInOpenState(1) + 10);
        $call = static function () {
            throw new \RuntimeException('error');
        };
        $result = TryCall::call([$this->circuitBreaker, 'call'], $call);
        $this->assertEquals(State::HALF_OPEN, $this->circuitBreaker->getState());
        $this->assertTrue($result->isFailure());
        $this->assertInstanceOf(\RuntimeException::class, $result->getException());
        $events = $this->eventDispatcher->getEvents();
        $this->assertEquals([
            CircuitBreakerOnStateTransition::class,
            CircuitBreakerOnError::class,
        ], array_map('get_class', $events));
    }
}
