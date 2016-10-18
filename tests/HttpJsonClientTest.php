<?php
namespace kuiper\rpc\client;

use PHPUnit_Framework_TestCase as TestCase;
use ProxyManager\Factory\RemoteObjectFactory;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use kuiper\rpc\client\fixtures\ApiServiceInterface;
use kuiper\rpc\client\fixtures\Request;
use kuiper\rpc\client\fixtures\Item;
use kuiper\annotations\DocReader;
use kuiper\annotations\AnnotationReader;
use kuiper\serializer\Serializer;

class HttpJsonClientTest extends TestCase
{
    public function createClient()
    {
        $docReader = new DocReader();
        $serializer = new Serializer(new AnnotationReader(), $docReader);

        $requests = [];
        $history = Middleware::history($requests);
        $mock = new MockHandler();
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $client = new HttpClient(['handler' => $handler]);

        $factory = new RemoteObjectFactory(new HttpJsonClient($client, $serializer, $docReader));
        return [$factory->createProxy(ApiServiceInterface::class), $mock];
    }

    public function testClient()
    {
        list ($client, $mock) = $this->createClient();
        $mock->append(new Response(200, [], '{"id":"1","result":[{"name":"foo"}]}'));
        $result = $client->query($this->createRequest('foo'));
        // print_r($result);
        $this->assertTrue(is_array($result));
        $this->assertInstanceOf(Item::class, $result[0]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage invalid query
     */
    public function testException()
    {
        list ($client, $mock) = $this->createClient();
        $mock->append(new Response(200, [], '{"id":"1","error":{"code":-32000,"message":"invalid query","data":"YTozOntzOjU6ImNsYXNzIjtzOjI0OiJJbnZhbGlkQXJndW1lbnRFeGNlcHRpb24iO3M6NzoibWVzc2FnZSI7czoxMzoiaW52YWxpZCBxdWVyeSI7czo0OiJjb2RlIjtpOjA7fQ=="}}'));
        $result = $client->query($this->createRequest('foo'));
    }

    public function createRequest($query)
    {
        $request = new Request;
        $request->setQuery($query);
        return $request;
    }
}