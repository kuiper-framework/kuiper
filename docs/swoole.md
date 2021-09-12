# Server

Kuiper 对 Swoole 服务创建过程进行封装，使用 PSR-14 事件接口封装 swoole 事件处理，
对于 http request 事件处理使用 PSR-15 HTTP Handler，支持 PSR-7 Http Message。

## 应用配置

项目中需要先使用 composer 安装以下包：

```bash
composer require kuiper/kuiper
composer require symfony/console
composer require --dev kuiper/component-installer
```

在项目 `composer.json` 中添加 :

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
            ]
        }
    }
}
```

创建启动文件 `src/index.php` ：

```php
<?php

use kuiper\swoole\Application;
define('APP_PATH', dirname(__DIR__));
require APP_PATH . '/vendor/autoload.php';
Application::run();
```

这里定义了常量 `APP_PATH` ，在运行时可以通过 `Application::getInstance()->getBasePath()` 获取。

## 配置

`\kuiper\swoole\Application` 在初始化时加载配置。配置使用的是 [Properties](helper.md#Properties) 对象存储。配置加载方式有以下方式：

1. 通过命令行参数 `--config config.ini` 指定配置文件，使用 `parse_ini_file` 解析
2. 通过命令行参数 `--define key=value` 指定
3. 通过加载 `APP_PATH` 目录下的 `src/config.php` 

需要注意的是配置项约定使用 `application.` 作为前缀，目的是方便通过容器获取配置，不会因为[配置不存在抛出异常](di.md#配置项) 。
命令行使用 `--define` 指定配置时不需要包括 `application.` 前缀，例如 `--define env=dev` 设置 `application.env` 配置项的值为 dev。

通过命令行 `--config` 选项指定配置文件示例：
```
application.name=DemoApp
```

`src/index.php` 配置文件示例：
```php
<?php

return [
    'application' => [
        'name' => 'DemoApp'
    ]
];
```

在配置中我们可以使用 `\kuiper\helper\env()` 函数获取环境变量的值。环境变量的值配置可以通过 `.env` 文件设置。
在不同运行环境下，我们需要加载不同的环境变量。运行环境可以通过环境变量 `ENV` 或者配置项 `application.env` 设置。
例如：
```bash
ENV=dev php src/index.php
php src/index.php --define env=dev
```

在项目中可以使用以下文件设置环境变量的值：
```
.env                # 在所有的环境中被载入
.env.local          # 在所有的环境中被载入，一般配置为 git 忽略
.env.[mode]         # 只在指定的模式中被载入
.env.[mode].local   # 只在指定的模式中被载入，一般配置为 git 忽略
```

## Console Application

在入口文件 `src/index.php` 中 `Application::run()` 会创建 `\Symfony\Component\Console\Application` 对象，
并执行默认任务。默认任务通过 `application.default_command` 配置，在 `\kuiper\swoole\config\ServerConfiguration` 中
配置为 `\kuiper\swoole\ServerCommand`。所有 `application.commands` 中配置的命令和 `@\kuiper\di\annotation\Command`
注解标记的命令都可以加入到 Symfony Console Application 中被执行。

`application.commands` 配置是一个数组，key 为命令名，value 为命令执行的 Command 类名。例如：

```php
<?php

return [
    'application' => [
        'commands' => [
            'foo' => \app\command\FooCommand::class
        ]
    ]
];
```

在 kuiper di 扫描命名空间中的类使用 `@Command` 注解可以自动添加到命令列表中：

```php
<?php

namespace app\command;

use kuiper\di\annotation\Command;
use \Symfony\Component\Console\Command\Command as ConsoleCommand;

/**
 * @Command("foo")
 */
class FooCommand extends ConsoleCommand {
}
```

## 事件

事件分成以下几种类型：

- 进程生命周期事件
- 连接协议处理事件
- 任务处理事件

进程生命周期事件包括：

- BootstrapEvent 这个事件不是 swoole 事件，是个虚拟事件，在服务启动前调用，用于初始化服务器资源
- StartEvent 
- ShutdownEvent
- ManagerStartEvent
- ManagerStopEvent
- WorkerStartEvent
- WorkerStopEvent
- WorkerExitEvent
- WorkerErrorEvent

连接协议处理事件包括：

- ConnectEvent 
- CloseEvent
- RequestEvent
- ReceiveEvent
- PacketEvent
- OpenEvent
- HandShakeEvent
- MessageEvent
- PipeMessageEvent

任务处理事件包括：

- TaskEvent
- FinishEvent

在服务启动时，会添加 `application.listeners` 中的事件监听器，并添加默认的事件监听器，包括 
`\kuiper\swoole\listener\StartEventListener`, 
`\kuiper\swoole\listener\ManagerStartEventListener`,
`\kuiper\swoole\listener\WorkerStartEventListener`, 
和 `\kuiper\swoole\listener\TaskEventListener`。
并通过命名空间扫描注解 `@\kuiper\event\annotation\EventListener` 
自动添加监听器。

## 协程

swoole 协程和非协程在开发中会有差异，对于单元测试或者需要调试时会产生一些干扰。
kuiper swoole 包中对协程做了相应包装，可以在未开启协程情况，进行降级处理。

通过 `\kuiper\swoole\coroutine\Coroutine::enable()` 方法用协程编程。
使用 `Coroutine::isEnabled()` 判断是否启用协程编程。在 swoole 4以上版本，协程默认是开启的。
通过 swoole [服务 `enable_corountine` 配置](https://wiki.swoole.com/#/server/setting?id=enable_coroutine) 。

通过对协程是否开启的判断，可以让一些本来在协程开启时才有用的操作降级为只有一个协程的操作。
例如 `\kuiper\swoole\coroutine\Coroutine::getContext()` , `\kuiper\swoole\coroutine\Coroutine::defer()` 。

## 连接池

在协程之间不能共用 tcp 连接，必须使用[连接池](https://wiki.swoole.com/#/question/use?id=client-has-already-been-bound-to-another-coroutine) 进行管理连接。

连接池创建使用实例：

```php
<?php

$poolFactory = new \kuiper\swoole\pool\PoolFactory();
$poolFactory->create('db', function() {
    return new \PDO("mysql:host=mysql");
});
```

连接池有两个比较重要的配置项：

- max_connections 设置连接池中可以创建的连接对象个数上线
- wait_timeout 设置连接对象全部占用后，等待连接释放的时间（单位秒，可以有小数）

如果连接池中对象全部占用，并等待时间超过 wait_timeout 后，会抛出 `\kuiper\swoole\exception\PoolTimeoutException` 异常。 
如果出现这个异常，说明需要增大 max_connections 的数量。

## Server

在 kuiper swoole 中提供了 swoole 服务器和简单的 php 内置服务器。我们在生产环境使用 swoole 服务器，在开发调试时可以使用 php 
内置服务器。两种类型服务器通过 `application.server.enable_php_server` 配置开关切换。

当使用 swoole 服务器时，可以监听多个端口。多端口配置通过 `application.server.ports` 配置设置：

```php
<?php

return [
     'application' => [
         'server' => [
             'ports' => [
                 8000 => 'http',
                 8001 => 'tcp'
             ]
         ]
     ]
];
```

swoole 服务器的配置通过 `application.swoole` 配置。

## Task

Kuiper 对 swoole 中的任务进行简单的封装，更容易使用。
首先创建一个 Task 类：

```php
<?php

use kuiper\swoole\task\AbstractTask;

class MyTask extends AbstractTask
{
    private $arg;
    public function __construct($arg)
    {
        $this->arg = $arg;
    }
}
```

创建 Task 处理类
```php
<?php
use kuiper\swoole\task\ProcessorInterface;
use kuiper\swoole\task\TaskInterface;

class MyTaskProcessor implements ProcessorInterface
{
     public function process(TaskInterface $task)
     {
     }
}
```

投递任务：

```php
$container->get(kuiper\swoole\task\QueueInterface::class)
    ->put(new MyTask($arg));
```
