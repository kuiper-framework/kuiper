# Server

Kuiper encapsulates the Swoole service creation process with [PSR-14](https://www.php-fig.org/psr/psr-14/)
event interface for swoole event handling, and [PSR-15](https://www.php-fig.org/psr/psr-15/)
HTTP handler for http request event handling, supported  [PSR-7](https://www.php-fig.org/psr/psr-7/) Http Message。

## Installation

```bash
composer install kuiper/swoole:^0.8
```

Create the startup file `src/index.php`:

```php
<?php

use kuiperswooleApplication;
define('APP_PATH', dirname(__DIR__));
require APP_PATH . '/vendor/autoload.php';
Application::run();
```

The constant `APP_PATH` is defined here, which can be retrieved at runtime via `Application::getInstance()->getBasePath()`.

## Configuration

The `kuiper\swoole\Application::create` method creates an `Application` singleton object, which can be obtained via the `Application::getInstance()` method.
Configuration loading is performed in the `Application` constructor. There are several configuration loading methods:

1. Specify the configuration file with the command line argument `--config config.ini`, using `parse_ini_file` resolution
2. Specified via the command line arguments `--define key=value` or `-D key=value`
3. By loading `src/config.php` in the `APP_PATH` directory 

Noted that the configuration item convention uses `application.` as a prefix to avoid throw an exception because [configuration does not exist](di.md# configuration item).
The command line does not need to include the `application.` prefix when specifying the configuration using `--define`, e.g. `--define env=dev` sets the value of the `application.env` configuration item to dev.

Specify a sample configuration file via the command line `--config` option. The file is in ini format, for example:
```
application.name=DemoApp
```

`src/index.php` configuration file example:
```php
<?php

return [
    'application' => [
        'name' => 'DemoApp'
    ]
];
```

> configuration uses the [Properties](helper.md#Properties) object store. So you can get the configuration value by '.' way.

In the configuration we can use the `kuiper\helper\env()` function to get the value of the environment variable. The value configuration of environment variables can be set via the `.env` file.
In different operating environments, we need to load different environment variables. The runtime environment can be set via the environment variable `APP_ENV` or the configuration item `application.env`.
For example:
```bash
APP_ENV=dev php src/index.php
php src/index.php --define env=dev
```

In your project, you can use the following files to set the values of environment variables:
```
.env # is loaded in all environments
.env.local # is loaded in all environments and is generally configured to be ignored by git
.env. [mode] # is loaded only in the specified mode
.env. [mode].local # is loaded only in the specified mode and is generally configured to be ignored by git
```

## Console Application

`Application::run()` in the entry file `src/index.php` creates the `Symfony\Component\Console\Application` object.
and perform the default tasks. The default task is configured via `application.default_command`, in `kuiper\swoole\config\ServerConfiguration`
configured as `kuiper\swoole\ServerStartCommand`. All commands configured in `application.commands` and `kuiper\di\attribute\Command`
Annotation flagged commands can be added to the Symfony Console Application to be executed.

The `application.commands` configuration is an array with key as the command name and value as the command class name of the command executed. For example:

```php
<?php

return [
    'application' => [
        'commands' => [
            'foo' => appcommandFooCommand::class
        ]
    ]
];
```

Classes in the kuiper di scan namespace can be automatically added to the command list using `Command` attribute, for example:

```php
<?php

namespace app\command;

use kuiper\di\attribute\Command;
use Symfony\Component\Console\Command\Command as ConsoleCommand;

#[Command("foo")]
class FooCommand extends ConsoleCommand {
}
```

## events

Service events are divided into the following types:

- Process lifecycle events
- Connection protocols handle events
- Task processing events

Process lifecycle events include:

- BootstrapEvent is not a swoole event, but a dummy event that is called before the service starts and is used to initialize server resources
- StartEvent 
- ShutdownEvent
- ManagerStartEvent
- ManagerStopEvent
- WorkerStartEvent
- WorkerStopEvent
- WorkerExitEvent
- WorkerErrorEvent

Connection protocol processing events include:

- ConnectEvent 
- CloseEvent
- RequestEvent
- ReceiveEvent
- PacketEvent
- OpenEvent
- MessageEvent
- PipeMessageEvent

Task processing events include:

- TaskEvent
- FinishEvent

When the service starts, event listeners from 'application.listeners' are added, and default event listeners are added, including 
`\kuiper\swoole\listener\StartEventListener`, 
`\kuiper\swoole\listener\ManagerStartEventListener`,
`\kuiper\swoole\listener\WorkerStartEventListener`, 
and `\kuiper\swoole\listener\TaskEventListener`。
Listeners can also be added automatically via the namespace scan annotation `#[\kuiper\event\attribute\EventListener]` . 

## Coroutine

Swoole coroutines and non-coroutines can be different in development and can cause some noise for unit testing or debugging needs. The coroutines are packaged accordingly in Kuiper swoole, and can be downgraded when the coroutines are not enabled.

Use the `kuiper\swoole\coroutine\Coroutine::enable()` method to program with coroutine.
Use `Coroutine::isEnabled()` to determine whether coroutine programming is enabled. In Swoole 4 and above, coroutines are enabled by default.
Via swoole [service `enable_corountine` configuration] (https://wiki.swoole.com/#/server/setting?id=enable_coroutine).

By judging whether the coroutine is turned on, some operations that would have been useful when the coroutine is turned on can be reduced to operations with only one coroutine.
For example, `\kuiper\swoole\coroutine\Coroutine::getContext()` , `\kuiper\swoole\coroutine\Coroutine::defer()` .

## Connection pooling

TCP connections cannot be shared between coroutines and must be managed using [connection pooling](https://wiki.swoole.com/#/question/use?id=client-has-already-been-bound-to-another-coroutine).

Connection pooling creation usage instance:

```php
<?php

$poolFactory = new \kuiper\swoole\pool\PoolFactory();
$poolFactory->create('db', function() {
    return new PDO("mysql:host=mysql");
});
```

Connection pooling has two important configuration items:

- max_connections Set the number of connection objects that can be created in the connection pool
- wait_timeout Set the time (in seconds, can have decimals) to wait for the connection to be released after the connection object is fully occupied.

If all the objects in the connection pool are occupied and the wait time exceeds wait_timeout, the `kuiper\swoole\exception\PoolTimeoutException` exception is thrown. 
If this exception occurs, the number of max_connections needs to be increased.

## Server

A swoole server and a simple PHP built-in server are provided in Kuiper Swoole. We use the Swoole server in production and PHP when developing and debugging 
Built-in server. Both types of servers are switched through the `application.server.enable_php_server` configuration switch.

The configuration of the swoole server is set via `application.server.settings`. For example:

```php
return [
     'application' => [
         'server' => [
             'settings' => [
                'package_max_length' => 10*1024*1024,
             ]
         ]
     ]
];
```

When using a swoole server, multiple ports can be listened to. Multi-port configuration via 'application.server.ports' configuration settings:

```php
<?php

return [
     'application' => [
         'server' => [
             'ports' => [
                 8000 => 'http',
                 8001 => [
                    'protocol' => 'tcp',
                    'listener' => 'jsonRpcTcpReceiveEventListener'
                 ]
             ]
         ]
     ]
];
```

Service port configuration items include:
- Host service listening address, default is 0.0.0.0, listening on all IP addresses
- Protocol service type, currently supports HTTP and TCP 
- listener service listener processor, can be omitted when protocol is http, use the default listener processor, otherwise configure according to the service type, refer to [RPC] (rpc.md) service

## Task

Kuiper is a simple encapsulation of tasks in swoole that is easier to use.

First create a Task class:

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

Create a Task handling class
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

Delivery task:

```php
$container->get(kuiper\swoole\task\QueueInterface::class)
    ->put(new MyTask($arg));
```

Kuiper Swoole does not have a specific service implementation, so please use [kuiper/web](web.md), [kuiper/jsonrpc](rpc.md), or [kuiper/tars](tars.md) for business service development.

## Configuration items

| Configuration Item | Environment variables | Description |
|----------------------------|--------------------------------|--------------------------------------------------------|
| default_command            |                                | The default execution command is start |
| env                        | APP_ENV                        | prod; development                                       |
| enable_bootstrap_container | APP_ENABLE_BOOTSTRAP_CONTAINER | Whether to use a separate container object before the service starts (which allows code to be reloaded when the worker is restarted) |
| name                       | APP_NAME                       | App name |
| php_config_file            | APP_PHP_CONFIG_FILE            | Load PHP profile name |
| server.enable_php_server   | SERVER_ENABLE_PHP_SERVER       | Whether to use PHP built-in services (only partial functionality, only development and testing use) |
| server.settings            |                                | swoole service configuration item |
| server.port                | SERVER_PORT                    | Service port number | | server.ports               |                                | Multi-port listening or complex service configuration using |
| server.http_factory        | SERVER_HTTP_FACTORY            | HTTP Factory implementation, supporting Diactoros, Guzzle, Nyholm, default to DiactorOS |

## Command line arguments

| Parameter name | Description |
|------------------------|------------------------------|
| --config | <config-file> value is ini configuration file path |
| --define=foo=value     | Define configuration values that are automatically prefixed with 'application.' |
| －D foo=value           | --define parameter alias |

Next: [Web Server](web.md)
