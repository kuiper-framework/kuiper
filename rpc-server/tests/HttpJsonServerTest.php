<?php

namespace kuiper\rpc\server;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\DocReader;
use kuiper\annotations\DocReaderInterface;
use kuiper\di;
use kuiper\di\ContainerBuilder;
use kuiper\rpc\server\fixtures\ApiService;
use kuiper\rpc\server\fixtures\ApiServiceInterface;
use kuiper\rpc\server\fixtures\Calculator;
use kuiper\rpc\server\fixtures\CalculatorInterface;
use kuiper\rpc\server\middleware\JsonRpc;
use kuiper\rpc\server\util\HealthyCheckService;
use kuiper\rpc\server\util\HealthyCheckServiceInterface;
use kuiper\serializer\JsonSerializerInterface as SerializerInterface;
use kuiper\serializer\Serializer;
use PHPUnit_Framework_TestCase as TestCase;

class HttpJsonServerTest extends TestCase
{
    public function createServer()
    {
        $docReader = new DocReader();
        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            SerializerInterface::class => new Serializer(new AnnotationReader(), $docReader),
            DocReaderInterface::class => $docReader,
            CalculatorInterface::class => di\object(Calculator::class),
            ApiServiceInterface::class => di\object(ApiService::class),
            HealthyCheckServiceInterface::class => di\object(HealthyCheckService::class),
        ]);

        $resolver = new ServiceResolver();
        $resolver->setContainer($container = $builder->build());
        $resolver->add(CalculatorInterface::class);
        $resolver->add(ApiServiceInterface::class);
        $resolver->add(HealthyCheckServiceInterface::class);

        $server = new Server($resolver);
        $server->add(new JsonRpc());

        return $server;
    }

    public function testHandleOk()
    {
        $server = $this->createServer();
        $request = new Request('{"method":"'.addslashes(CalculatorInterface::class).'.multiply","id":"1","params":[10,10]}');
        $response = $server->serve($request, new Response());
        // print_r($response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('{"id":"1","jsonrpc":"1.0","result":100}', (string) $response->getBody());
    }

    public function testHealthyCheck()
    {
        $server = $this->createServer();
        $request = new Request(json_encode([
            'method' => HealthyCheckServiceInterface::class.'.ping',
            'id' => '1',
            'params' => [],
        ]));
        $response = $server->serve($request, new Response());
        $result = json_decode((string) $response->getBody(), true);
        $this->assertEquals('pong', $result['result']);
    }
}
