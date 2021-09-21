# RPC 服务

RPC 服务可以有多种协议，这里以 jsonrpc 协议为例说明 RPC 服务端和客户端使用方式。

## 安装

```bash
composer require kuiper/jsonrpc:^0.6
```

## JSON RPC Server

jsonrpc 服务传输方式可以使用 http 协议和 tcp 协议两种服务。
在 composer.json 中添加 `kuiper\jsonrpc\config\JsonRpcHttpServerConfiguration` 启用 http 服务，例如：

```json
{
    "scripts": {
        "container-config": "kuiper\\component\\ComponentInstaller::generate"
    },
    "extra": {
        "kuiper": {
            "config-file": "src/container.php",
            "whitelist": [
                "kuiper/*"
            ],
            "configuration": [
                "kuiper\\jsonrpc\\config\\JsonRpcHttpServerConfiguration"
            ]
        }
    }
}
```

如果要使用 tcp 协议，则替换为 `"kuiper\\jsonrpc\\config\\JsonRpcTcpServerConfiguration"`。例如：

```json
{
    "scripts": {
        "container-config": "kuiper\\component\\ComponentInstaller::generate"
    },
    "extra": {
        "kuiper": {
            "config-file": "src/container.php",
            "whitelist": [
                "kuiper/*"
            ],
            "configuration": [
                "kuiper\\jsonrpc\\config\\JsonRpcTcpServerConfiguration"
            ]
        }
    }
}
```

## 服务注册

项目中命名空间扫描注解 `@\kuiper\jsonrpc\annotation\JsonRpcService` 标记的类都将注册为对外的 jsonrpc 服务对象。
服务名可以由 `@JsonRpcService` 注解中 `service` 属性值指定，当未指定时可以由 `@JsonRpcService` 的接口类名生成。
接口类名和实现类名必须有包含关系，例如 `UserService` 和 `UserServiceImpl`。 服务名是由接口名将命名空间分隔符替换为 `.` 生成，
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

当使用字符串 key 时，key 为服务名称。value 可以时字符或者是一个数组，数组可包含以下配置：
- service 服务名
- class 服务实现类在容器中的注册 ID
- version 服务版本号

## 客户端

Json RPC 客户端可以通过代理对象调用。首先在 composer.json 中添加配置：

```json
{
    "scripts": {
        "container-config": "kuiper\\component\\ComponentInstaller::generate"
    },
    "extra": {
        "kuiper": {
            "config-file": "src/container.php",
            "whitelist": [
                "kuiper/kuiper"
            ],
            "configuration": [
                "kuiper\\web\\http\\GuzzleHttpMessageFactoryConfiguration",
                "kuiper\\jsonrpc\\config\\JsonRpcClientConfiguration"
            ]
        }
    }
}
```

项目中命名空间扫描注解 `@\kuiper\jsonrpc\annotation\JsonRpcClient` 标记的类都将注册为 jsonrpc 客户端。
服务名可以由 `@JsonRpcClient` 注解中 `service` 属性值指定，当未指定时可以由 `@JsonRpcClient` 标记的类名生成。
服务名是由接口名将命名空间分隔符替换为 `.` 生成，
例如，`app\service\UserService` 服务名为 `app.service.UserService`。

除了使用注解标记，也可以通过配置 `application.jsonrpc.client.clients` 注册服务对象，例如：

```php
<?php

return [
    'application' => [
        'jsonrpc' => [
            'client' => [
                'clients' => [
                    UserService::class,
                    'calculator' => CalculatorService::class
                ]
            ]
        ]
    ]
];
```

当 key 为字符串时，key 值为容器中注册ID值，下面查询 `application.jsonrpc.client.options` 配置项时也会使用
这个key值。

服务地址通过配置项添加：

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

调用服务：
```php
<?php
use kuiper\swoole\Application;
$container = Application::create()->getContainer();
$ret = $container->get(FooService::class)->foo();
```

客户端配置项包括：
- middleware 通用中间件
- http_options 设置公共 http 配置参数，参考 [Guzzle 请求参数](https://docs.guzzlephp.org/en/stable/request-options.html)
- tcp_options 设置公共 tcp 配置参数，参考 `\kuiper\swoole\constants\ClientSettings`
- options 按客户端类设置配置参数

客户端类配置包括
- endpoint 设置服务器地址
- service 服务名
- middleware 设置中间件
- 其他 http 或 tcp 配置参数

## 实现

RPC 的服务端和客户端都使用类似 PSR-15 Http Handlers 的接口实现。
请求处理器接口 `\kuiper\rpc\RpcRequestHandlerInterface` 处理 `\kuiper\rpc\RpcRequestInterface` ,
并返回 `\kuiper\rpc\RpcResponseInterface`。
中间件接口 `\kuiper\rpc\MiddlewareInterface` 和 Http 中间件作用相同，可以用于处理 rpc 请求和响应。

jsonrpc 服务端中间件可以通过配置项 `application.jsonrpc.server.middleware` 设置。

jsonrpc 客户端中间件可以通过配置项 `application.jsonrpc.client.middleware` 设置，也可以在
`application.jsonrpc.client.options` 中对每个客户端类配置。

## 服务发现

通过使用 consul 等服务注册中心可以自动发现服务。

```bash
composer require kuiper/rpc-registry:^0.6
```

在 `src/config.php` 中添加 consul 服务地址配置：

```php
[
    'application' => [
        'consul' => [
            'base_uri' => "http://consul:8500",
        ]
    ]
]
```
consul 其他配置参数参考 [http-client](http-client.md) 。

对于 rpc 服务端，需要添加事件监听器：

```php
[
    'application' => [
        'listeners' => [
            \kuiper\rpc\server\listener\ServiceDiscoveryListener::class
        ],
    ]
]
```
在服务启动时会自动将当前服务地址注册到 consul 中。

对于 rpc 客户端，需要添加中间件：

```php
[
    'application' => [
        'jsonrpc' => [
            'client'=> [
                'middleware' => [
                    \kuiper\rpc\client\middleware\ServiceDiscovery::class,
                ]
            ]
        ]
    ]
]
```

服务发现相关配置项：

| 配置项                                           | 说明                                               |
|--------------------------------------------------|----------------------------------------------------|
| application.server.service_disovery.type         | 服务端服务注册类型，目前只支持 consul                    |
| application.client.service_disovery.type         | 客户端服务发现类型，目前只支持 consul                    |
| application.client.service_disovery.load_balance | 负载均衡算法，可选值 round_robin, random, equality |
