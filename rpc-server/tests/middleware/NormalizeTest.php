<?php

namespace kuiper\rpc\server\middleware;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\DocReader;
use kuiper\rpc\server\fixtures;
use kuiper\rpc\server\Request;
use kuiper\rpc\server\Response;
use kuiper\rpc\server\Server;
use kuiper\rpc\server\ServiceResolver;
use kuiper\rpc\server\TestCase;
use kuiper\serializer\Serializer;

class JsonRpcTest extends TestCase
{
    public function createServer()
    {
        $docReader = new DocReader();
        $serializer = new Serializer(new AnnotationReader(), $docReader);
        $server = new Server($this->resolver = new ServiceResolver());
        $server->add(new JsonRpc());
        $server->add(new Normalize($this->resolver, $serializer, $docReader));
        $this->resolver->add(new fixtures\ApiService());

        return $server;
    }

    public function testServe()
    {
        $server = $this->createServer();
        $request = new Request(json_encode([
            'id' => 1,
            'method' => sprintf('%s.query', fixtures\ApiService::class),
            'params' => [['query' => 'foo']],
        ]));
        $response = $server->serve($request, new Response());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('{"id":1,"jsonrpc":"1.0","result":[{"name":"foo"}]}', (string) $response->getBody());
    }
}
