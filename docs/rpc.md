# RPC 服务

RPC 服务可以有多种协议，这里以 jsonrpc 协议为例说明 RPC 服务端和客户端使用方式。

## 安装

```bash
composer require kuiper/jsonrpc:^0.8
```

## JSON RPC Server

### 服务配置
jsonrpc 服务传输方式可以使用 http 协议和 tcp 协议两种服务。
在 composer.json 中添加 `\kuiper\jsonrpc\config\JsonRpcServerConfiguration` 服务配置，例如：

```json
{
    "extra": {
        "kuiper": {
            "configuration": [
                "kuiper\\jsonrpc\\config\\JsonRpcServerConfiguration"
            ]
        }
    }
}
```

在 `applicaton.server.ports` 中需要配置服务传输协议和监听器：

```php
[
    'application' => [
        'server' => [
            'ports' => [
                env('SERVER_PORT', '8000') => [
                    'protocol' => 'http',
                    'listener' => 'jsonRpcHttpRequestListener'
                ]
            ]
        ]
    ] 
]
```

对于 http 传输协议，配置的监听器为 `jsonRpcHttpRequestListener`，对于 tcp 传输协议配置监听器为 `jsonRpcTcpReceiveEventListener` 。

### 服务注册

项目中命名空间扫描注解 `\kuiper\jsonrpc\attribute\JsonRpcService` 标记的类都将注册为对外的 jsonrpc 服务对象。
服务名可以由 `JsonRpcService` 注解中 `service` 属性值指定，当未指定时可以由所标记接口类名生成。 服务名是由接口名将命名空间分隔符替换为 `.` 生成，
例如，`app\service\UserService` 服务名为 `app.service.UserService`。

除了使用注解标记，也可以通过配置 `application.jsonrpc.server.services` 注册服务对象，例如：

```php
<?php

return [
    'application' => [
        'jsonrpc' => [
            'server' => [
                'services' => [
                    UserService::class,
                    'calculator' => CalculatorService::class
                ]
            ]
        ]
    ]
];
```

`application.jsonrpc.server.services` 的配置 key 值为服务名称。value 可以时字符或者是一个数组，数组可包含以下配置：
- service 服务名
- class 服务实现类在容器中的注册 ID
- version 服务版本号

### 配置项

| 配置项                       | 环境变量                      | 说明                              |
|---------------------------|---------------------------|---------------------------------|
| jsonrpc.server.log_file   | JSONRPC_SERVER_LOG_FILE   | 访问日志文件名，默认为 jsonrpc-server.json |
| jsonrpc.server.log_params | JSONRPC_SERVER_LOG_PARAMS | 访问日志中是否记录请求参数                   |
| jsonrpc.server.out_params |                           | 是否启用入参赋值                        |
| jsonrpc.server.middleware |                           | 中间件配置                           |
| jsonrpc.server.services   |                           | 注册服务                            |

`jsonrpc.server.out_params` 配置项用于配置是否启用入参赋值。当我们的服务声明包含需要赋值的入参，例如：
```php
class RegistryService 
{
    public function getService(string $name, ?array &$result): int;
}
```
通过设置 `out_params` 为 true 可以将函数返回值和入参，合并为数组作为结果响应。

## 客户端

### 客户端配置

Json RPC 客户端可以通过代理对象调用。

项目中命名空间扫描注解 `\kuiper\jsonrpc\attribute\JsonRpcClient` 标记的类都将注册为 jsonrpc 客户端。
服务名可以由 `JsonRpcClient` 注解中 `service` 属性值指定。

除了使用注解标记，也可以通过配置 `application.jsonrpc.client.clients` 注册服务对象，配置数组的 key 值为容器注册的 ID。例如：

```php
<?php

return [
    'application' => [
        'jsonrpc' => [
            'client' => [
                'clients' => [
                    UserService::class,
                    'calculator' => CalculatorService::class,
                    'calculator2' => [
                       'class' => CalculatorService::class,
                       'endpoint' => 'http://localhost:8001'
                    ]
                ]
            ]
        ]
    ]
];
```

`application.jsonrpc.client.options` 可以按类名配置缺省参数：

```php
[
    'application' => [
        'jsonrpc' => [
            'client'=> [
                'options' => [
                    FooService::class => [
                        'endpoint' => 'tcp://localhost:8000'
                    ]
                ]
            ]
        ]
    ]
];
```

客户端配置项包括
- endpoint 设置服务器地址
- protocol 服务传输协议，支持 http 和 tcp，默认为 http
- service 服务名
- out_params 是否启用入参赋值，参考服务端 `out_params` 配置说明
- middleware 设置中间件
- 其他 http 或 tcp 配置参数

调用服务：
```php
<?php
$result = $container->get(FooService::class)->foo();
```


### 配置项

| 配置项                            | 环境变量                           | 说明                             |
|--------------------------------|--------------------------------|--------------------------------|
| jsonrpc.client.log_file        | JSONRPC_CLIENT_LOG_FILE        | 请求日志文件                         |
| jsonrpc.client.log_sample_rate | JSONRPC_CLIENT_LOG_SAMPLE_RATE | 记录日志取样率，默认为1，设置为0不记录日志         |
| jsonrpc.client.log_params      | JSONRPC_CLIENT_LOG_PARAMS      | 请求日志是否记录请求参数                   |
| jsonrpc.client.protocol        | JSONRPC_CLIENT_PROTOCOL        | 服务传输协议，支持 http 和 tcp，默认值为 http |
| jsonrpc.client.middleware      |                                | 中间件                            |
| jsonrpc.client.http_options    |                                | 设置公共 http 配置参数                 |
| jsonrpc.client.tcp_options     |                                | 设置公共 tcp 配置参数                  |
| jsonrpc.client.clients         |                                | 注册客户端                          |
| jsonrpc.client.options         |                                | 客户端配置                          |

`http_options` 可配置参数参考 [HttpClient](http-client.md)，`tcp_options` 可配置参数参考 swoole tcp 配置参数。

## 实现

RPC 的服务端和客户端都使用类似 PSR-15 Http Handlers 的接口实现。
请求处理器接口 `\kuiper\rpc\RpcRequestHandlerInterface` 处理 `\kuiper\rpc\RpcRequestInterface` ,
并返回 `\kuiper\rpc\RpcResponseInterface`。
中间件接口 `\kuiper\rpc\MiddlewareInterface` 和 Http 中间件作用相同，可以用于处理 rpc 请求和响应。

jsonrpc 服务端中间件可以通过配置项 `application.jsonrpc.server.middleware` 设置。

jsonrpc 客户端中间件可以通过配置项 `application.jsonrpc.client.middleware` 设置，也可以在
`application.jsonrpc.client.options` 中对每个客户端类配置。

