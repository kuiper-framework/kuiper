# RPC Tutorial

RPC(Remote Procedure Call) 远程过程调用是分布式系统常见的通信方法。RPC 框架存在的目的是为了让分布式服务系统中不同服务之间的调用像本地调用一样简单。下面我们使用一个简单的例子演示如何使用 Kuiper 开发一个 RPC 服务。

RPC 的实现都包含传输协议和序列化协议两个部分。这里我们使用简单的 [JsonRPC](https://www.jsonrpc.org/specification) 作为序列化协议，使用[\r\n 作为结束符](https://wiki.swoole.com/#/learn?id=tcp%e6%95%b0%e6%8d%ae%e5%8c%85%e8%be%b9%e7%95%8c%e9%97%ae%e9%a2%98)的tcp协议作为传输协议实现 jsonrpc 服务。

## 创建项目

我们还是使用项目模板创建项目：

```bash
composer create-project kuiper/skeleton myapp
```

使用项目模板创建项目时，需要回答提供一些项目配置选项。首先需要指定服务类型：

```
Choose server type: 
[1] Http Web Server
[2] JsonRPC Web Server
[3] JsonRPC TCP Server
[4] Tars HTTP Web Server
[5] Tars TCP RPC Server
Make your selection (1): 3
```

这次我们选择第3项 JsonRPC TCP 服务。

## 文件目录结构

生成项目目录结构如下：

```
.
|-- composer.json
|-- console
|-- .env
|-- resources
|   `-- serve.sh
`-- src
    |-- config.php
    |-- container.php
    |-- index.php
    `-- service
        |-- HelloService.php
        `-- HelloServiceImpl.php
```

项目文件与 Web 项目基本相同。jsonrpc 服务通过 `@kuiper\jsonrpc\annotation\JsonRpcService` 注解标记：

```php
<?php

declare(strict_types=1);

namespace app\service;

use kuiper\jsonrpc\annotation\JsonRpcService;

/**
 * @JsonRpcService
 */
class HelloServiceImpl implements HelloService
{
    /**
     * {@inheritdoc}
     */
    public function hello(string $message): string
    {
        return "hello $message";
    }
}
```

使用 `composer serve` 启动服务后，通过 telnet 来验证我们的服务：

```bash
$ telnet localhost 8000
{"jsonrpc": "2.0", "id": 1, "method": "app.service.HelloService.hello", "params": ["kuiper"]}
{"jsonrpc":"2.0","id":1,"result":"hello kuiper"}
```

## 客户端调用

我们通过新建另一个项目来调用启用的服务。

```bash
composer create-project kuiper/skeleton myapp2
```

还是选择第3项 JsonRPC TCP Server 作为服务类型。删除新项目中 src/service/HelloServiceImpl.php 实现。
修改 src/service/HelloService.php 如下：

```php
<?php

declare(strict_types=1);


namespace app2\service;

use kuiper\jsonrpc\annotation\JsonRpcClient;

/**
 * @JsonRpcClient(service="app.service.HelloService")
 */
interface HelloService
{
    /**
     * @param string $message
     *
     * @return string
     */
    public function hello(string $message): string;
}
```

我们在 HelloService 上添加 `@kuiper\jsonrpc\annotation\JsonRpcClient` 注解，并设置注解的 service 属性为我们启动服务的服务名称。

在 `src/config.php` 中添加配置，设置服务调用地址：

```php
<?php

declare(strict_types=1);

use function kuiper\helper\env;

use app2\service\HelloService;

return [
    'application' => [
        'jsonrpc' => [
            'client' => [
                'options' => [
                    HelloService::class => [
                        'endpoint' => 'tcp://localhost:8000'
                    ]
                ],
            ]
        ]
    ],
];
```

最后我们写一个测试脚本 `test.php`，验证服务调用过程：

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use kuiper\swoole\Application;
use app2\service\HelloService;

$service = Application::create()->getContainer()->get(HelloService::class);
echo $service->hello('kuiper'), "\n";
```

执行 
