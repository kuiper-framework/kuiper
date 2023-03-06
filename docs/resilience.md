# Resilience

kuiper resilience 用于 RPC 容错处理库。resilience 库代码参考 [resilience4j](https://resilience4j.readme.io/docs) 项目。

## 安装

```bash
composer require kuiper/resilience:^0.8
```

## 重试

使用方式：

```php
$container->get(RetryFactory::class)->create('name1')->call($callable);
```

retry 组件的配置项包括：

| 配置属性               | 默认值     | 描述                                            |
|--------------------|---------|-----------------------------------------------|
| max_attempts       | 3       | 最大尝试次数(包括第一次尝试初始调用)                           |
| wait_duration      | 500(ms) | 重试尝试等待时间                                      |
| interval_function  | 无       | 修改失败后等待时间间隔的函数。默认未设置，等待时间使用 wait_duration 设置值 |
| retry_on_result    | 无       | 回调函数，对方法调用结果判断是否应该重试结果。true 表示需要重试            |
| retry_on_exception | 无       | 回调函数，对方法调用产生的异常判断是否应该重试异常。true 表示需要重试         |
| retry_exceptions   | 空       | 配置异常类名列表，这些异常必须重试，支持异常子类型                     |
| ignore_exceptions  | 空       | 配置异常类名列表，这些异常不会重试，支持异常子类型                     |

配置项通过 `application.client.retry` 配置项设置，`application.client.retry.default` 可以设置默认配置项。
每个服务可以通过服务名设置配置项。

`RetryFactory::create` 名字可以使用 `{service}::{method}`，读取配置时可以使用 `{service}` 查询。

回调函数原型：
- `interval_function(int $attempts, int $waitDuration): int`
- `retry_on_result(Retry $retry, $result): bool`
- `retry_on_exception(\Exception $exception): bool`

当发生重试时，会触发事件：

| 事件名                | 事件发生条件                  |
|--------------------|-------------------------|
| RetryOnRetry       | 重试调用方法时                 |
| RetryOnSuccess     | 重试调用后结果成功时              |
| RetryOnError       | 重试调用后结果失败并达到最大重试次数，中止重试 |
| RetryOnIgnoreError | 调用异常属于可忽略的异常时           |

## 断路器

CircuitBreaker 通过具有三种正常状态的有限状态机实现：`CLOSED`，`OPEN` 和 `HALF_OPEN`
以及两个特殊状态 `DISABLED` 和 `FORCED_OPEN`。当熔断器关闭 (`CLOSED` 状态) 时，所有的请求都会通过熔断器。
如果失败率超过设定的阈值，熔断器就会从关闭转换到打开 (`OPEN` 状态)，这时所有的请求都会被拒绝。
当经过一段时间后，熔断器会从打开转换到半开 (`HALF_OPEN` 状态)，这时仅有一定数量的请求会被放入，并重新计算失败率，
如果失败率超过阈值，则变为打开状态，如果失败率低于阈值，则变为关闭状态。

![CircuitBreaker State Transition](https://files.readme.io/39cdd54-state_machine.jpg)


使用方式：

```php
<?php

$container->get(CircuitBreakerFactory::class)->create('name1')->call($callable);
```

CircuitBreaker 组件配置项包括：

| 配置属性                                         | 默认值         | 描述                                 |
|----------------------------------------------|-------------|------------------------------------|
| failure_rate_threshold                       | 50          | 失败率百分比阈值                           |
| slow_call_rate_threshold                     | 100         | 慢调用百分比阈值                           |
| slow_call_duration_threshold                 | 10000 （ms)  | 慢调用时间阈值，方法调用时长超过此阈值视为慢调用           |
| permitted_number_of_calls_in_half_open_state | 10          | 半开状态下允许的调用数                        |
| sliding_window_type                          | COUNT_BASED | 统计窗口类型，可选值： COUNT_BASED、TIME_BASED |
| sliding_window_size                          | 100         | 窗口大小                               |
| minimum_number_of_calls                      | 100         | 统计失败率时最小调用次数                       |
| wait_duration_in_open_state                  | 60000 (ms)  | 从打开状态转换为半开状态时长                     |
| record_exceptions                            | 空           | 配置异常类名列表，这些异常认为是失败调用，支持异常子类型       |
| ignore_exceptions                            | 空           | 配置异常类名列表，这些异常认为是可忽略异常，支持异常子类型      |

配置项通过 `application.client.circuitbreaker` 配置项设置，`application.client.circuitbreaker.default` 可以设置默认配置项。
每个服务可以通过服务名设置配置项。

事件列表：

| 事件名                                  | 事件发生条件             |
|--------------------------------------|--------------------|
| CircuitBreakerOnError                | 调用结果失败时            |
| CircuitBreakerOnSuccess              | 调用结果为成功时           |
| CircuitBreakerOnFailureRateExceeded  | 调用结果失败并达到失败率百分比阈值时 |
| CircuitBreakerOnIgnoredError         | 调用异常属于可忽略的异常时      |
| CircuitBreakerOnSlowCallRateExceeded | 慢调用达到阈值时           |
| CircuitBreakerOnStateTransition      | 断路器发生状态变更时         |
