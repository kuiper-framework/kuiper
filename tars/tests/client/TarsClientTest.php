<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\client\RpcExecutorFactory;
use kuiper\rpc\transporter\HttpTransporter;
use kuiper\tars\core\TarsMethodFactory;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\fixtures\HelloService;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\ResponsePacket;
use kuiper\tars\stream\TarsOutputStream;
use kuiper\tars\type\MapType;
use kuiper\tars\type\PrimitiveType;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;

class TarsClientTest extends TestCase
{
    public function testName()
    {
        $proxyGenerator = new TarsProxyGenerator();
        $generatedClass = $proxyGenerator->generate(HelloService::class);
        $generatedClass->eval();
        $class = $generatedClass->getClassName();
        $packet = new ResponsePacket();
        $packet->iRequestId = 1;
        $packet->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), [
            '' => TarsOutputStream::pack(PrimitiveType::string(), 'hello world'),
        ]);
        $mock = new MockHandler([
            new Response(200, [], (string) $packet->encode()),
        ]);
        $requests = [];
        $history = Middleware::history($requests);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $methodFactory = new TarsMethodFactory();
        $requestFactory = new TarsRequestFactory(new RequestFactory(), new StreamFactory(), $methodFactory, '', 1);
        $responseFactory = new TarsResponseFactory();
        $rpcClient = new RpcClient($transporter, $responseFactory);
        /** @var HelloService $proxy */
        $proxy = new $class(new RpcExecutorFactory($requestFactory, $rpcClient));
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello world');

        /** @var TarsRequestInterface $request */
        $request = $requests[0]['request'];
        $packet = RequestPacket::decode((string) $request->getBody());
        $this->assertEquals('app.hello.HelloObj', $packet->sServantName);
        $this->assertEquals('hello', $packet->sFuncName);
    }
}
