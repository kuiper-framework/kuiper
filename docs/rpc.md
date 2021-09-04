# RPC Server

## JSON RPC Server

jsonrpc 服务支持 tcp 协议和 http 协议两种服务，在

以 JsonRPC 服务为例。

```bash
composer require kuiper/kuiper
composer require symfony/console
composer require --dev kuiper/component-installer
```

在 composer.json 中添加配置：

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
                "kuiper\\swoole\\config\\FoundationConfiguration",
                "kuiper\\swoole\\config\\ServerConfiguration",
                "kuiper\\serializer\\SerializerConfiguration",
                "kuiper\\jsonrpc\\config\\JsonRpcTcpServerConfiguration"
            ]
        }
    }
}
```

添加入口文件 src/index.php

```php
<?php

use kuiper\swoole\Application;
define('APP_PATH', dirname(__DIR__));
require APP_PATH . '/vendor/autoload.php';
Application::run();
```

在 src/config.php 中添加配置:

```php
<?php

return [
    'application' => [
        'swoole' => [
            'task_worker_num' => 1,
            'worker_num' => 1,
            'daemonize' => 0,
            'ports' => [
                8002 => 'tcp'
            ]
        ],
        'logging' => [
            'path' => APP_PATH . '/logs',
        ]
    ]
];
```

服务启动：

```bash
php src/index.php
```

项目中 `@\kuiper\jsonrpc\annotation\JsonRpcService` 标记的类都将注册为对外的 jsonrpc 服务。
jsonrpc 中 method 定义