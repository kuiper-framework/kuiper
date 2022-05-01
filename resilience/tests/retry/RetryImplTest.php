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

namespace kuiper\resilience\retry;

use kuiper\event\InMemoryEventDispatcher;
use kuiper\resilience\core\MockClock;
use kuiper\resilience\core\SimpleCounterFactory;
use kuiper\resilience\core\TryCall;
use kuiper\resilience\retry\event\RetryOnError;
use kuiper\resilience\retry\event\RetryOnRetry;
use PHPUnit\Framework\TestCase;

class RetryImplTest extends TestCase
{
    private InMemoryEventDispatcher $eventDispatcher;

    private Retry $retry;

    private RetryConfig $config;

    private MockClock $clock;

    protected function setUp(): void
    {
        $this->eventDispatcher = new InMemoryEventDispatcher();
        $this->clock = new MockClock();
        $this->config = RetryConfig::ofDefaults();
        $this->retry = new RetryImpl('test', $this->config, $this->clock, new SimpleCounterFactory());
        $this->retry->setEventDispatcher($this->eventDispatcher);
    }

    public function testNotRetry(): void
    {
        $call = static function () {
        };
        $this->retry->call($call);
        $this->assertEquals(1, $this->retry->getMetrics()->getNumberOfSuccessfulCallsWithoutRetryAttempt());
    }

    public function testShouldReturnAfterThreeAttempts(): void
    {
        $time = $this->clock->getTimeInMillis();
        $calls = 0;
        $call = static function () use (&$calls) {
            ++$calls;
            throw new \RuntimeException('error');
        };
        $result = TryCall::call([$this->retry, 'call'], $call);
        $this->assertTrue($result->isFailure());
        $this->assertInstanceOf(\RuntimeException::class, $result->getException());
        $events = $this->eventDispatcher->getEvents();
        $this->assertEquals([
            RetryOnRetry::class,
            RetryOnRetry::class,
            RetryOnError::class,
        ], array_map('get_class', $events));
        $this->assertEquals(1, $this->retry->getMetrics()->getNumberOfFailedCallsWithRetryAttempt());
        $this->assertEquals(3, $calls);
        $duration = $this->clock->getTimeInMillis() - $time;
        $this->assertEquals($duration, $this->config->getWaitDuration() * ($calls - 1));
    }
}
