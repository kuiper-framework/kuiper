# TARS 

[TARS](https://github.com/TarsCloud/Tars/blob/master/README.zh.md) 是腾讯开源的高性能RPC开发框架。在 kuiper 中使用 tars 协议
的服务和使用 jsonrpc 协议的服务基本相同。

## Tars 服务端

Tars 服务目前只提供 tcp 协议的服务。首先在 composer.json 中配置：

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
                "kuiper\\tars\\config\\TarsServerConfiguration"
            ]
        }
    }
}
```

和 jsonrpc 服务不同，在入口文件中需要使用 `\kuiper\tars\TarsApplication` 创建应用，例如：

```php
<?php

use kuiper\tars\TarsApplication;

define('APP_PATH', dirname(__DIR__));

require APP_PATH . '/vendor/autoload.php';

TarsApplication::run();
```

服务启动命令也需要修改为:

```bash
php src/index.php --config config.conf
```

这个 config.conf 文件为 tars 框架为服务生成的模板配置文件。

## 服务定义

tars 服务通过 [tars 文件](https://github.com/TarsCloud/TarsDocs/blob/master/base/tars-protocol.md) 定义。
tars 文件可以通过 [tars-generator](https://github.com/wenbinye/tars-generator) 命令生成 PHP 代码。
和 `@JsonRpcService` 注解不同，`@TarsServant` 注解标记在生成的接口类上。在服务端实现类上还需要使用 `@\kuiper\di\annotation\Service`。

因为 tars 框架有服务注册功能，不需要使用 consul 这样的服务注册服务。在 `TarsClientConfiguration` 中已经配置好服务发现的中间件。

