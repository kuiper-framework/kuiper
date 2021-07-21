<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use kuiper\swoole\monolog\CoroutineIdProcessor;
use kuiper\swoole\pool\PoolFactory;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swoole\Coroutine;

class HttpClientFactoryTest extends TestCase
{
    public function testName()
    {
        $logger = new Logger('', [new ErrorLogHandler()], [new CoroutineIdProcessor()]);
        $httpClientFactory = new HttpClientFactory(new PoolFactory());
        $httpClientFactory->setLogger($logger);
        $httpClient = $httpClientFactory->create([
            'logging' => true,
            'log-format' => 'debug',
        ]);
        $response = $httpClient->get('http://baidu.com');
        // print_r($response);
        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testCoroutine()
    {
        $logger = new Logger('', [new ErrorLogHandler()], [new CoroutineIdProcessor()]);
        $httpClientFactory = new HttpClientFactory(new PoolFactory());
        $handlerStack = HandlerStack::create();
        $handlerStack->push(Middleware::log($logger, new MessageFormatter()));

        $tasks = array_fill(0, 3, function () use ($httpClientFactory, $handlerStack) {
            $httpClient = $httpClientFactory->create(['handler' => $handlerStack]);
            $response = $httpClient->get('http://baidu.com');
            // print_r($response);
            self::assertInstanceOf(ResponseInterface::class, $response);
        });
        array_walk($tasks, [Coroutine::class, 'create']);
    }
}
