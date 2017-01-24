<?php
namespace kuiper\rpc\server;

use PHPUnit_Framework_TestCase as TestCase;
use kuiper\rpc\server\fixtures\CalculatorInterface;
use kuiper\rpc\server\fixtures\Calculator;
use kuiper\rpc\server\fixtures\ApiServiceInterface;
use kuiper\rpc\server\fixtures\ApiService;
use kuiper\rpc\server\util\HealthyCheckService;
use kuiper\rpc\server\util\HealthyCheckServiceInterface;
use kuiper\rpc\server\response\ResponseInterface;
use kuiper\rpc\server\request\RequestFactory;
use kuiper\di\ContainerBuilder;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\DocReader;
use kuiper\annotations\DocReaderInterface;
use kuiper\serializer\JsonSerializerInterface as SerializerInterface;
use kuiper\serializer\Serializer;
use InvalidArgumentException;
use UnexpectedValueException;

class JsonServerTest extends TestCase
{
    public function createServer()
    {
        $container = ContainerBuilder::buildDevContainer();
        $docReader = new DocReader();
        $container->set(SerializerInterface::class, new Serializer(new AnnotationReader(), $docReader));
        $container->set(DocReaderInterface::class, $docReader);
        $container->set(CalculatorInterface::class, new Calculator());
        $container->set(ApiServiceInterface::class, new ApiService());
        $container->set(HealthyCheckServiceInterface::class, new HealthyCheckService());
        
        $server = new JsonServer($container);
        $server->add(CalculatorInterface::class);
        $server->add(ApiServiceInterface::class);
        $server->add(HealthyCheckServiceInterface::class);
        return $server;
    }

    public function testHandle()
    {
        $server = $this->createServer();
        $request = RequestFactory::fromString('{"method":"'.addslashes(CalculatorInterface::class).'.multiply","id":"1","params":[10,10]}');
        $response = $server->handle($request);
        // print_r($response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('{"id":"1","result":100}', $response->getBody());
    }

    public function testError()
    {
        $server = $this->createServer();
        $request = RequestFactory::fromString('{"method":"'.addslashes(CalculatorInterface::class).'.divide","id":"1","params":[10,0]}');
        $response = $server->handle($request);
        // print_r($response);
        $this->assertContains('-32602', $response->getBody());
    }

    public function testInvalidParam()
    {
        $server = $this->createServer();
        $request = RequestFactory::fromString('{"method":"'.addslashes(CalculatorInterface::class).'.divide","id":"1","params":[10,"a"]}');
        $response = $server->handle($request);
        // print_r($response);
        $this->assertContains('-32602', $response->getBody());
    }

    public function testHandleObject()
    {
        $server = $this->createServer();
        $request = RequestFactory::fromString(json_encode([
            "method" => ApiServiceInterface::class.'.query',
            "id" => "1",
            "params" => [ ["query" => 'foo'] ]
        ]));
        $response = $server->handle($request);
        // print_r($response);
        $this->assertEquals($response->getBody(), '{"id":"1","result":[{"name":"foo"}]}');
    }

    public function testHandleEmptyQuery()
    {
        $server = $this->createServer();
        $request = RequestFactory::fromString(json_encode([
            "method" => ApiServiceInterface::class.'.query',
            "id" => "1",
            "params" => [ ["query" => "foo"] ]
        ]));
        // print_r($request);
        $response = $server->handle($request);
        // print_r($response);
        $this->assertEquals($response->getBody(), '{"id":"1","result":[{"name":"foo"}]}');
    }

    public function testException()
    {
        $server = $this->createServer();
        $request = RequestFactory::fromString(json_encode([
            "method" => ApiServiceInterface::class.'.query',
            "id" => "1",
            "params" => [ ["query" => null] ]
        ]));
        $response = $server->handle($request);
        // print_r($response);
        $result = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('error', $result);
        $e = unserialize(base64_decode($result['error']['data']));
        // print_r($e);
        $this->assertEquals('InvalidArgumentException', $e['class']);
    }

    public function testHealthyCheck()
    {
        $server = $this->createServer();
        $request = RequestFactory::fromString(json_encode([
            "method" => HealthyCheckServiceInterface::class.'.ping',
            "id" => "1",
            "params" => [],
        ]));
        $response = $server->handle($request);
        $result = json_decode($response->getBody(), true);
        $this->assertEquals('pong', $result['result']);
    }
}
