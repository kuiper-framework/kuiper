# Resilience

kuiper resilience is used in the RPC fault-tolerant processing library. This library is inspired by [resilience4j](https://resilience4j.readme.io/docs).

## Installation

```bash
composer require kuiper/resilience:^0.8
```

## Retry

Usage:

```php
$container->get(RetryFactory::class)->create('name1')->call($callable);
```

The configuration items of the retry component include:

| Configuration Properties | Default value | Description |
|--------------------|---------|-----------------------------------------------|
| max_attempts       | 3       | Maximum number of attempts (including first attempt initial call) |
| wait_duration      | 500(ms) | Retry attempt wait time |
| interval_function  | None | Modify the function for the wait interval after failure. The default is not set, and the wait time is set using wait_duration value |
| retry_on_result    | None | The callback function determines whether the result of the method call should be retried. true indicates that a retry | is required
| retry_on_exception | None | The callback function determines whether the exception generated by the method call should be retried. true indicates that a retry | is required
| retry_exceptions   | empty | Configure a list of exception class names that must be retried, with exception subtypes supported
| ignore_exceptions  | empty | Configure a list of exception class names that are not retried, and support exception subtypes |

Configuration items are set through the `application.client.retry` configuration item, and `application.client.retry.default` can set the default configuration item.
Each service can set configuration items by service name.

The `RetryFactory::create` name can be used `{service}::{method}`, and the `{service}` query can be used to read the configuration.

Callback function prototype:
- `interval_function(int $attempts, int $waitDuration): int`
- `retry_on_result(Retry $retry, $result): bool`
- `retry_on_exception(Exception $exception): bool`

When a retry occurs, an event is triggered:

| Event Name | Event Occurrence Condition |
|--------------------|-------------------------|
| RetryOnRetry       | Retry when the method is called|
| RetryOnSuccess     | Retry the call after the result succeeds when |
| RetryOnError       | After retrying the call, the result fails and the maximum number of retries is reached, abort the retry |
| RetryOnIgnoreError | When calling an exception is a negligible exception |

## Circuit breaker

CircuitBreaker is implemented through a finite state machine with three normal states: `CLOSED`, `OPEN` and `HALF_OPEN`
and two special statuses, `DISABLED` and `FORCED_OPEN`. When the fuse is closed (`CLOSED` state), all requests go through the fuse.
If the failure rate exceeds the set threshold, the circuit breaker transitions from closed to open (`OPEN` state), at which point all requests are rejected.
When a period of time has elapsed, the fuse transitions from open to half-open (`HALF_OPEN` state), at which point only a certain number of requests are put in and the failure rate is recalculated.
If the failure rate exceeds the threshold, it becomes on, and if the failure rate falls below the threshold, it becomes closed.

![CircuitBreaker State Transition](https://files.readme.io/39cdd54-state_machine.jpg)

Usage:

```php
<?php

$container->get(CircuitBreakerFactory::class)->create('name1')->call($callable);
```

CircuitBreaker component configuration items include:

| Configuration Properties | Default value | Description |
|----------------------------------------------|-------------|------------------------------------|
| failure_rate_threshold                       | 50          | Failure Rate % Threshold |
| slow_call_rate_threshold                     | 100         | Slow call percentage threshold |
| slow_call_duration_threshold                 | 10000 （ms)  | Slow call time threshold, method call longer than this threshold is considered slow call |
| permitted_number_of_calls_in_half_open_state | 10          | Number of calls allowed in the half-open state |
| sliding_window_type                          | COUNT_BASED | Statistics window type, optional values: COUNT_BASED, TIME_BASED |
| sliding_window_size                          | 100         | Window size |
| minimum_number_of_calls                      | 100         | Minimum number of calls when counting failure rates |
| wait_duration_in_open_state                  | 60000 (ms)  | Length of transition from open to half-open |
| record_exceptions                            | empty | Configure a list of exception class names that are considered failed calls and support exception subtypes |
| ignore_exceptions                            | empty | Configure a list of exception class names that are considered ignorable exceptions and support exception subtypes

Configuration items are set through the `application.client.circuitbreaker` configuration item, and `application.client.circuitbreaker.default` can set default configuration items.
Each service can set configuration items by service name.

List of events:

| Event Name | Event Occurrence Condition |
|--------------------------------------|--------------------|
| CircuitBreakerOnError                | When the result of the call fails |
| CircuitBreakerOnSuccess              | When the result of the call is success|
| CircuitBreakerOnFailureRateExceeded  | When the call results fail and the failure rate percentage threshold is reached|
| CircuitBreakerOnIgnoredError         | When calling an exception is a negligible exception |
| CircuitBreakerOnSlowCallRateExceeded | When a slow call reaches a threshold |
| CircuitBreakerOnStateTransition      | When the state of the circuit breaker changes |

Next: [Http Client](http-client.md)