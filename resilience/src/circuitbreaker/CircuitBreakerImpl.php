<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnError;
use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnIgnoredError;
use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnStateTransition;
use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnSuccess;
use kuiper\resilience\circuitbreaker\exception\IllegalStateTransitionException;
use kuiper\resilience\circuitbreaker\exception\ResultWasFailureException;
use kuiper\resilience\core\Clock;
use kuiper\resilience\core\Counter;
use kuiper\resilience\core\CounterFactory;
use kuiper\resilience\core\Metrics;
use kuiper\resilience\core\MetricsFactory;
use Psr\EventDispatcher\EventDispatcherInterface;

class CircuitBreakerImpl implements CircuitBreaker
{
    private const NOT_PERMITTED_CALLS = '.not_permitted_calls';
    private const CALLS_HALF_OPEN = '.calls_half_open';

    /**
     * @var string
     */
    private $name;

    /**
     * @var CircuitBreakerConfig
     */
    private $config;

    /**
     * @var CounterFactory
     */
    private $counterFactory;

    /**
     * @var Metrics
     */
    private $halfStateMetrics;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var CircuitBreakerMetrics
     */
    private $metrics;

    /**
     * @var Counter
     */
    private $permittedNumberOfCallsInHalfOpenState;

    /**
     * @var CircuitBreakerState
     */
    private $state;

    /**
     * @var StateStore
     */
    private $stateStore;

    public function __construct(
        string $name,
        CircuitBreakerConfig $config,
        Clock $clock,
        StateStore $stateStore,
        CounterFactory $counterFactory,
        MetricsFactory $metricsFactory,
        EventDispatcherInterface $eventDispatcher)
    {
        $this->name = $name;
        $this->config = $config;
        $this->clock = $clock;
        $this->counterFactory = $counterFactory;

        $metrics = $metricsFactory->create($name, $config->getSlidingWindowType(), $config->getSlidingWindowSize());
        $this->metrics = new CircuitBreakerMetricsImpl(
            $counterFactory->create($name.self::NOT_PERMITTED_CALLS),
            $metrics,
            $config
        );
        $this->halfStateMetrics = $metricsFactory->create($name.'.half_state', SlideWindowType::COUNT_BASED(), $config->getPermittedNumberOfCallsInHalfOpenState());
        $this->permittedNumberOfCallsInHalfOpenState = $counterFactory->create($name.self::CALLS_HALF_OPEN);
        $this->eventDispatcher = $eventDispatcher;
        $this->stateStore = $stateStore;
        $this->state = new ClosedState($this);
        $this->reset();
    }

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
            $this->state->onError($duration, $exception);
        } elseif ($this->config->shouldIgnoreException($exception)) {
            $this->state->releasePermission();
            $this->eventDispatcher->dispatch(new CircuitBreakerOnIgnoredError($this, $duration, $exception));
        } elseif ($this->config->isFailureException($exception)) {
            $this->eventDispatcher->dispatch(new CircuitBreakerOnError($this, $duration, $exception));
            $this->state->onError($duration, $exception);
        } else {
            $this->onSuccess($duration);
        }
    }

    /**
     * @param mixed $result
     */
    public function onResult(int $duration, $result): void
    {
        if ($this->config->isFailureResult($result)) {
            $this->onError($duration, new ResultWasFailureException($this, $result), true);
        } else {
            $this->onSuccess($duration);
        }
    }

    public function reset(): void
    {
        $state = $this->stateStore->getState($this->name);
        switch ($state->value) {
            case State::OPEN:
                $openAt = $this->stateStore->getOpenAt($this->name);
                if (0 === $openAt) {
                    $this->reset();

                    return;
                }

                $this->state = new OpenState($this, 1, $openAt);
                break;
            case State::HALF_OPEN:
                if (State::HALF_OPEN !== $this->state->getState()->value) {
                    $this->resetPermittedNumberOfCallsInHalfOpenState();
                }
                $this->state = $this->createHalfOpenState();
                break;
            case State::FORCED_OPEN:
                $this->state = new ForcedOpenState($this, 1);
                break;
            case State::DISABLED:
                $this->state = new DisabledState();
                break;
            default:
                $this->state = new ClosedState($this);
        }
    }

    public function onSuccess(int $duration): void
    {
        $this->eventDispatcher->dispatch(new CircuitBreakerOnSuccess($this, $duration));
        $this->state->onSuccess($duration);
    }

    public function tryAcquirePermission(): bool
    {
        return $this->state->tryAcquirePermission();
    }

    public function getCurrentTimestamp(): int
    {
        return $this->clock->getTimeInMillis();
    }

    /**
     * {@inheritDoc}
     */
    public function call(callable $call, ...$args)
    {
        return $this->decorate($call)(...$args);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetrics(): CircuitBreakerMetrics
    {
        return $this->metrics;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getConfig(): CircuitBreakerConfig
    {
        return $this->config;
    }

    public function getState(): State
    {
        return $this->state->getState();
    }

    public function getClock(): Clock
    {
        return $this->clock;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function stateTransition(State $newState, callable $newStateGenerator): void
    {
        $currentState = $this->state->getState();
        if (!$currentState->canTransitionTo($newState)) {
            throw new IllegalStateTransitionException("Cannot transit state from {$currentState} to {$newState}");
        }
        $this->state = $newStateGenerator($this->state);
        $this->stateStore->setState($this->name, $this->state->getState());
        $this->eventDispatcher->dispatch(new CircuitBreakerOnStateTransition($this, $currentState, $newState));
    }

    public function transitionToOpenState(): void
    {
        $this->stateTransition(State::OPEN(), function (CircuitBreakerState $currentState): OpenState {
            $currentTimestamp = $this->getCurrentTimestamp();
            $this->counterFactory->create($this->name.'.open_at')->set($currentTimestamp);

            return new OpenState($this, $currentState->attempts() + 1, $currentTimestamp);
        });
    }

    public function transitionToHalfOpen(): void
    {
        $this->stateTransition(State::HALF_OPEN(), function (CircuitBreakerState $currentState): HalfOpenState {
            $this->resetPermittedNumberOfCallsInHalfOpenState();

            return $this->createHalfOpenState($currentState->attempts());
        });
    }

    public function transitionToCloseState(): void
    {
        $this->stateTransition(State::CLOSED(), function (CircuitBreakerState $currentState): ClosedState {
            return new ClosedState($this);
        });
    }

    public function transitionToForcedOpenState(): void
    {
        $this->stateTransition(State::FORCED_OPEN(), function (CircuitBreakerState $currentState): ForcedOpenState {
            return new ForcedOpenState($this, $currentState->attempts() + 1);
        });
    }

    public function transitionToDisabledState(): void
    {
        $this->stateTransition(State::DISABLED(), function (CircuitBreakerState $currentState): DisabledState {
            return new DisabledState();
        });
    }

    private function createHalfOpenState(int $attempts = 0): HalfOpenState
    {
        $this->halfStateMetrics->reset();
        $circuitBreakerMetrics = new CircuitBreakerMetricsImpl(
            $this->counterFactory->create($this->name.self::NOT_PERMITTED_CALLS),
            $this->halfStateMetrics,
            $this->config
        );

        return new HalfOpenState($this, $attempts + 1, $circuitBreakerMetrics, $this->permittedNumberOfCallsInHalfOpenState);
    }

    private function resetPermittedNumberOfCallsInHalfOpenState(): void
    {
        $this->permittedNumberOfCallsInHalfOpenState->set($this->config->getPermittedNumberOfCallsInHalfOpenState());
    }
}
