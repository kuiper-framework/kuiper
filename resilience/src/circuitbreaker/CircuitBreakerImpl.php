<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnError;
use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnIgnoredError;
use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnSuccess;
use kuiper\resilience\circuitbreaker\exception\ResultWasFailureException;
use Psr\EventDispatcher\EventDispatcherInterface;

class CircuitBreakerImpl implements CircuitBreaker
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var CircuitBreakerState
     */
    private $state;

    /**
     * @var CircuitBreakerConfig
     */
    private $config;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * {@inheritDoc}
     */
    public function decorate(callable $call): callable
    {
        return function () use ($call) {
            $this->state->acquirePermission();
            $start = $this->getCurrentTimestamp();
            try {
                $result = call_user_func_array($call, func_get_args());
                $duration = $this->getCurrentTimestamp() - $start;
                $this->onResult($duration, $result);

                return $result;
            } catch (\Exception $exception) {
                $duration = $this->getCurrentTimestamp() - $start;
                $this->onError($duration, $exception);
                throw $exception;
            }
        };
    }

    public function onError(int $duration, \Exception $exception, bool $shouldNotIgnore = false): void
    {
        if ($shouldNotIgnore) {
            $this->eventDispatcher->dispatch(new CircuitBreakerOnError($this, $duration, $exception));
            $this->handleError($duration, $exception);
        } elseif ($this->config->shouldIgnoreException($exception)) {
            $this->releasePermission();
            $this->eventDispatcher->dispatch(new CircuitBreakerOnIgnoredError($this, $duration, $exception));
        } elseif ($this->config->isFailureException($exception)) {
            $this->eventDispatcher->dispatch(new CircuitBreakerOnError($this, $duration, $exception));
            $this->handleError($duration, $exception);
        } else {
            $this->onSuccess($duration);
        }
    }

    public function onResult(int $duration, $result): void
    {
        if (!$this->config->validateResult($result)) {
            $this->onError($duration, new ResultWasFailureException($this, $result), true);
        } else {
            $this->onSuccess($duration);
        }
    }

    public function onSuccess(int $duration): void
    {
        $this->eventDispatcher->dispatch(new CircuitBreakerOnSuccess($this, $duration));
        $this->state->onSuccess($duration);
    }

    public function getCurrentTimestamp(): int
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * {@inheritDoc}
     */
    public function call(callable $call, array $args)
    {
        return call_user_func($this->decorate($call), $args);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetrics(): CircuitBreakerMetrics
    {
        // TODO: Implement getMetrics() method.
    }

    private function handleError(int $duration, \Exception $exception): void
    {
        if (State::CLOSED === $this->state->value()) {
            checkIfThresholdsExceeded(circuitBreakerMetrics.onError(duration));
        } elseif (State::OPEN === $this->state->value()) {
            circuitBreakerMetrics.onError($duration);
        }
    }

    private function releasePermission(): void
    {
        if (in_array($this->state->value(), [State::CLOSED, State::OPEN], true)) {
        }
    }

    private function tryAcquirePermission(): bool
    {
        if (State::CLOSED === $this->state->value()) {
        } elseif (State::OPEN === $this->state->value()) {
        }
    }

    private function acquirePermission(): void
    {
        if (State::CLOSED === $this->state->value()) {
        } elseif (State::OPEN === $this->state->value()) {
            if (!$this->tryAcquirePermission()) {
                throw new CallNotPermittedException();
            }
        }
    }
}
