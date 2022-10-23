<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\tars\client;

use GuzzleHttp\Psr7\HttpFactory;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocatorImpl;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\ServerPort;
use kuiper\tars\core\TarsRequestLogFormatter;
use kuiper\tars\fixtures\HelloService;
use kuiper\tars\server\ErrorHandler;
use kuiper\tars\server\TarsServerMethodFactory;
use kuiper\tars\server\TarsServerRequestFactory;
use kuiper\tars\server\TarsServerResponseFactory;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\ResponsePacket;
use kuiper\tars\stream\TarsOutputStream;
use kuiper\tars\type\MapType;
use kuiper\tars\type\PrimitiveType;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class TarsServerTest extends TestCase
{
    public function testRpcCall()
    {
        $mockService = \Mockery::mock(HelloService::class);
        $mockService->shouldReceive('hello')
            ->andThrow(new \InvalidArgumentException('invalid arg message'));
        // ->andReturn('hello world');

        $services['app.hello.HelloObj'] = new Service(
            new ServiceLocatorImpl('app.hello.HelloObj'),
            $mockService,
            ['hello'],
            new ServerPort('localhost', 7000, ServerType::TCP)
        );
        $httpFactory = new HttpFactory();

        $logHandler = new TestHandler();
        $logger = new \Monolog\Logger('test', [$logHandler]);
        $errorHandler = new ErrorHandler($httpFactory);
        $errorHandler->setLogger($logger);

        $serverResponseFactory = new TarsServerResponseFactory($httpFactory, $httpFactory);
        $accessLog = new AccessLog(new TarsRequestLogFormatter());
        $accessLog->setLogger($logger);
        $requestHandler = new RpcServerRpcRequestHandler($services, $serverResponseFactory, $errorHandler, [$accessLog]);

        $rpcMethodFactory = new TarsServerMethodFactory('app.hello', $services);
        $serverRequestFactory = new TarsServerRequestFactory($rpcMethodFactory, $services);
        $serverRequestFactory->setLogger($logger);

        $request = $httpFactory->createRequest('POST', sprintf('//%s:%d', 'localhost', 7000));

        $packet = new RequestPacket();
        $packet->iRequestId = 1;
        $packet->sServantName = 'app.hello.HelloObj';
        $packet->sFuncName = 'hello';
        $packet->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), [
            'name' => TarsOutputStream::pack(PrimitiveType::string(), 'world'),
        ]);
        $request->getBody()->write((string) $packet->encode());
        $serverRequest = $serverRequestFactory->createRequest($request);
        $response = $requestHandler->handle($serverRequest);
        // print_r($logHandler->getRecords());

        $packet = ResponsePacket::decode((string) $response->getBody());
        // print_r($packet);
        $this->assertEquals(100000, $packet->iRet);
    }
}
