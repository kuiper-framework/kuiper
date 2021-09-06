# RPC 服务

RPC 服务可以有多种协议，这里以 jsonrpc 协议为例说明 RPC 服务端和客户端使用方式。

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
                "kuiper/kuiper"
            ],
            "configuration": [
                "kuiper\\jsonrpc\\config\\JsonRpcHttpServerConfiguration"
            ]
        }
    }
}
```

如果要使用 tcp 协议，则替换为 `"kuiper\\jsonrpc\\config\\JsonRpcTcpServerConfiguration"`。并且由于 laminas-diactoros
的 uri 不支持 `tcp://` 协议，还需要添加 `"kuiper\\web\\http\\GuzzleHttpMessageFactoryConfiguration"`。

## 服务注册

jsonrpc 中 method 由两部分构成 `{service_name}.{method}`，service_name 是服务名，method 是服务对象中的方法。
项目中在命名空间扫描 `@\kuiper\jsonrpc\annotation\JsonRpcService` 标记的类都将注册为对外的 jsonrpc 服务对象。
服务名可以由 `@JsonRpcService` 注解中 `service` 属性值指定，当未指定时可以由 `@JsonRpcService` 的接口类名生成。
接口类名和实现类名必须有包含关系，例如 `UserService` 和 `UserServiceImpl`。 服务名是由接口名将命名空间分隔符替换为 `.` 生成，
例如，`app\services\UserService` 服务名为 `app.services.UserService`。

除了使用注解标记，也可以通过配置 `application.jsonrpc.server.services` 注册服务对象，例如：

```php
<?php

return [
    'application' => [
        'jsonrpc' => [
            'server' => [
                'services' => [
                    UserService::class,
                    'calculator' => Calculator::class
                ]
            ]
        ]
    ]
];
```

## 客户端

## 服务发现


