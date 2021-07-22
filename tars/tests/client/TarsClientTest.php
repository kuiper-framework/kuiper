<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use kuiper\annotations\AnnotationReader;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\rpc\transporter\HttpTransporter;
use kuiper\tars\core\MethodMetadataFactory;
use kuiper\tars\fixtures\HelloService;
use kuiper\tars\stream\ResponsePacket;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;

class TarsClientTest extends TestCase
{
    public function testName()
    {
        $proxyGenerator = new TarsProxyGenerator(new ReflectionDocBlockFactory());
        $generatedClass = $proxyGenerator->generate(HelloService::class);
        $generatedClass->eval();
        $class = $generatedClass->getClassName();
        $packet = new ResponsePacket();
        $packet->iRequestId = 1;
        $packet->
        $mock = new MockHandler([
            new Response(200, [], (string) $packet->encode()),
        ]);
        $requests = [];
        $history = Middleware::history($requests);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $methodMetadataFactory = new MethodMetadataFactory(AnnotationReader::getInstance());
        $requestFactory = new TarsRpcRequestFactory(new RequestFactory(), new StreamFactory(), $methodMetadataFactory, 1);
        $responseFactory = new TarsRpcResponseFactory($methodMetadataFactory);
        /** @var HelloService $proxy */
        $proxy = new $class(new TarsRpcClient($transporter, $requestFactory, $responseFactory));
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello world');
    }
}
