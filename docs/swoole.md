# Swoole 

对 Swoole 服务创建过程进行一些封装，使用 PSR-14 事件接口封装 swoole 事件处理，
特别的对于 http request 事件处理使用 PSR-15 HTTP Handler，从而支持 PSR-7 消息模型。

这是 http 服务实现：

```php
<?php

use kuiper\swoole\http\DiactorosServerRequestFactory;
use kuiper\swoole\http\SimpleResponseSender;
use kuiper\swoole\listener\HttpRequestEventListener;
use kuiper\swoole\ServerConfig;
use kuiper\swoole\ServerPort;
use kuiper\swoole\ServerType;
use kuiper\swoole\SwooleServer;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

$logger = new NullLogger();
$eventDispatcher = new EventDispatcher();
$port = new ServerPort("0.0.0.0", 9527, ServerType::HTTP());
$serverConfig = new ServerConfig("demo", [], [$port]);
$server = new SwooleServer($serverConfig, $eventDispatcher);
$server->setLogger($logger);

$handler = new class implements RequestHandlerInterface {
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();
        return $responseFactory->createResponse()
            ->withBody($streamFactory->createStream("hello world!"));
    }
};
$serverRequestFactory = new DiactorosServerRequestFactory();
$responseSender = new SimpleResponseSender();
$httpRequestEventListener = new HttpRequestEventListener($serverRequestFactory, $handler, $responseSender);
$httpRequestEventListener->setLogger($logger);

$listeners = [$httpRequestEventListener];
foreach ($listeners as $listener) {
    $eventDispatcher->addListener($listener->getSubscribedEvent(), $listener);
}
$server->start();
```

## 事件

事件分成以下几种类型：

- 进程生命周期事件
- 连接协议处理事件
- 任务处理事件

进程生命周期事件包括：

- BeforeStartEvent 这个事件不是 swoole 事件，是个虚拟事件，在服务启动前调用，用于初始化服务器资源
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
