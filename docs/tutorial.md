# Tutorial

Kuiper 使用 composer 管理项目依赖。创建 Kuiper 项目最好的方式是使用 `kuiper/skeleton` 项目模板来初始化：

```bash
composer create-project kuiper/skeleton app
```

使用项目模板创建项目时，需要回答提供一些项目配置选项。首先需要指定服务类型：

```
Choose server type: 
[1] Http Web Server
[2] JsonRPC Web Server
[3] JsonRPC TCP Server
[4] Tars HTTP Web Server
[5] Tars TCP RPC Server
Make your selection (1):
```

我们先从最简单的 HTTP 服务开始。

## 文件目录结构

生成项目目录结构如下：

```
.
|-- composer.json
|-- console
|-- .env
|-- resources
|   |-- serve.sh
|   `-- views
`-- src
    |-- application
    |   `-- controller
    |       `-- IndexController.php
    |-- config.php
    |-- container.php
    `-- index.php
```

`src/index.php` 这个文件是项目的入口文件:

```php
<?php

declare(strict_types=1);

use kuiper\swoole\Application;

define('APP_PATH', dirname(__DIR__));

require APP_PATH.'/vendor/autoload.php';

Application::run();
```

这个入口文件非常简单，把启动过程都封装在 `kuiper\swoole\Application::run()` 函数中，后面我们会详细介绍启动过程。

在服务启动后，Kuiper 框架会扫描项目命名空间下所有使用 `@kuiper\di\annotation\Controller` 注解的类，这里 `src/application/controller/IndexController.php` 满足条件：


```php
<?php

namespace app\application\controller;

use kuiper\di\annotation\Controller;
use kuiper\web\AbstractController;
use kuiper\web\annotation\GetMapping;
use Slim\Exception\HttpUnauthorizedException;

/**
 * @Controller
 */
class IndexController extends AbstractController
{
    /**
     * @GetMapping("/")
     */
    public function index(): void
    {
        $this->getResponse()->getBody()->write("<h1>It works!</h1>\n");
    }
}
```

在发现 `@Controller` 注解标记的控制器类后，Kuiper 框架会扫描控制器类中使用 `@RequestMapping` 相关注解，将这些注解转换成路由规则添加在 Web 应用中。

## 启动服务

和一般 PHP-FPM 服务不同，Kuiper 服务必须使用 CLI 方式运行。在项目根目录通过命令行 `composer serve` 来启动。当 Console 界面显示服务启动后，便可通过 cURL 或浏览器访问服务。

> composer 命令执行有超时限制，可以通过 `composer config -g process-timeout 0` 设置为永不超时。

Kuiper 提供多种服务启动方式选择。通常情况使用上面 `composer serve` 启动服务。当需要设置启动服务的参数时，可以通过 `./console start` 或者直接使用 `php src/index.php start` 启动。当系统安装 [fswatch](https://github.com/emcrisostomo/fswatch) 后，可以通过 `./resource/serve.sh` 脚本启动服务，可以实现修改 src 目录下文件自动重启服务。

下一节: [RPC Tutorial](rpc-tutorial.md)
