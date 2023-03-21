# Tutorial

Kuiper uses composer to manage project dependencies. The best way to create a Kuiper project is to initialize it using the `kuiper/skeleton` project template:

```bash
composer create-project kuiper/skeleton:^0.2 app
```

When you create a project using a project template, you need to answer to provide some project configuration options. You first need to specify the service type:

```
Choose server type: 
[1] Http Web Server
[2] JsonRPC Web Server
[3] JsonRPC TCP Server
[4] Tars HTTP Web Server
[5] Tars TCP RPC Server
Make your selection (1):
```

Let's start with the simplest HTTP service.

## File directory structure

The build project directory structure is as follows:

```
.
|-- composer.json
|-- console
|-- .env
|-- resources
|   |-- serve.sh
|   `-- views
|      `-- index.html
`-- src
    |-- application
    |   `-- controller
    |       `-- IndexController.php
    |-- config.php
    |-- container.php
    `-- index.php
```

The `src/index.php` file is the entry file:

```php
<?php

declare(strict_types=1);
use kuiper\swoole\Application;
define('APP_PATH', dirname(__DIR__));
require APP_PATH.' /vendor/autoload.php';
Application::run();
```

This entry file is very simple. The startup process is encapsulated in the `kuiper\swoole\Application::run()` function, which we will cover in detail later.

After the service starts, the Kuiper framework scans all classes in the project namespace that use the `kuiper\di\attribute\Controller` annotation,
where `src/application/controller/IndexController.php` satisfies the conditions:

```php
<?php

namespace app\application\controller;

use kuiper\di\attribute\Controller;
use kuiper\web\AbstractController;
use kuiper\web\attribute\GetMapping;

#[Controller]
class IndexController extends AbstractController
{
    #[GetMapping("/")]
    public function index(): void
    {
        $this->getResponse()->getBody()->write("<h1>It works!</h1> n");
    }
}
```

After discovering a controller class marked with `#[Controller]` annotations, the Kuiper framework scans the controller class
for `#[RequestMapping]` attributes and converts these annotations into routing rules to add to the web application.

## Start the service

Unlike general PHP-FPM services, Kuiper services must be run using CLI.
Start at the project root via the command line `composer serve`.
When the Console interface shows that the service is started, the service can be accessed through a cURL or browser.

> composer command execution has a timeout limit, which can be set to never time out by `composer config -g process-timeout 0`.

Kuiper offers a variety of service startup options. Usually the `composer serve` above is used to start the service.
When you need to set parameters to start the service, you can start it by `./console start` or directly using `php src/index.php start`.
When the system installs [fswatch](https://github.com/emcrisostomo/fswatch), you can start the service through the `./resource/serve.sh` script,
which can automatically restart the service by modifying the files in the src directory.

Next: [RPC Tutorial](rpc-tutorial.md)
