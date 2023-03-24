# RPC Tutorial

RPC (Remote Procedure Call) Remote procedure call is a common communication method in distributed systems.
The RPC framework exists to make calls between different services in a distributed service system as simple as local calls.
Let's use a simple example to demonstrate how to develop an RPC service using Kuiper.

The implementation of RPC consists of two parts: a transport protocol and a serialization protocol.
Here we use simple [JsonRPC](https://www.jsonrpc.org/specification) as the serialization protocol
and http protocol as the transport protocol to implement the jsonRPC service.

## Create the project

We still create a project using a project template:

```bash
composer create-project kuiper/skeleton:^0.2 app
```

This time we choose the JsonRPC Web server:

```
Choose server type: 
[1] Http Web Server
[2] JsonRPC Web Server
[3] JsonRPC TCP Server
[4] Tars HTTP Web Server
[5] Tars TCP RPC Server
Make your selection (1): 2
```

## File directory structure

The build project directory structure is as follows:

```
.
|-- composer.json
|-- console
|-- .env
|-- resources
|   `-- serve.sh
|-- tars
|   |-- config.json
|   `-- servant
|       `-- hello.tars
`-- src
    |-- config.php
    |-- container.php
    |-- index.php
    |-- servant
    |   `-- HelloServant.php
    `-- application
        `-- HelloServantImpl.php
```

The project file is essentially the same as a Web project. The jsonrpc service is marked with `#JsonRpcService` annotations:

```php
<?php

declare(strict_types=1);

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 0.6
 */

namespace app\servant;

use kuiper\jsonrpc\attribute\JsonRpcService;

#[JsonRpcService(service: "HelloObj")]
interface HelloServant
{
    public function say(string $message): string;

}
```

This file is generated from the `tars/serant/hello.tars` file via the `composer gen` command.

Let's check out `application/HelloServantImpl.php`

```php
<?php

declare(strict_types=1);

namespace app\application;

use kuiper\di\attribute\Service;
use app\servant\HelloServant;

#[Service]
class HelloServantImpl implements HelloServant
{
    /**
     * {@inheritdoc}
     */
    public function say(string $message): string
    {
        return "hello $message";
    }
}
```

After starting the service with `composer serve`, verify our service via the cURL command:

```bash
$ curl -d '{"jsonrpc": "2.0", "id": 1, "method": "HelloObj.say", "params": ["kuiper"]}' localhost:7000
{"jsonrpc":"2.0","id":1,"result":"hello kuiper"}
```

## Client invocation

We invoke the service by creating another new project.

```bash
composer create-project kuiper/skeleton:^0.2 app2
```

Select Http Server as the service type for item 1. Then add a dependency:

```bash
cd app2
composer require kuiper/rpc-client:^0.8 
```

Add file src/integration/HelloServant.php as follows:

```php
<?php

declare(strict_types=1);

namespace app2\integration;

use kuiper\jsonrpc\attribute\JsonRpcClient;

#[JsonRpcClient(service: "HelloObj")]
interface HelloServant
{
    public function say(string $message): string;

}
```

We add the `#JsonRpcClient` annotation on HelloService and set the service property
of the annotation to the service name of the service that starts the service.

Add a configuration in `src/config.php` to set the service endpoint:

```php
<?php

declare(strict_types=1);

use function kuiperhelperenv;

use app2\integration\HelloSerant;

return [
    'application' => [
        'jsonrpc' => [
            'client' => [
                'options' => [
                    HelloServant::class => [
                        'endpoint' => 'http://localhost:7000'
                    ]
                ],
            ]
        ]
    ],
];
```

Finally, we write a test script 'test.php' to verify the service call process:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use kuiper\swoole\Application;
use app2\integration\HelloServant;

$service = Application::create()->getContainer()-> get(HelloServant::class);
echo $service->say('kuiper'), "n";
```

Execute 'php test.php' to see the RPC call results:

```bash
$ php test.php
hello kuiper
```

Next: [TARS Tutorial](tars-tutorial.md)