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
        $server->add(new JsonRpcErrorHandler());
        $this->resolver->add(new fixtures\ApiService());

        return $server;
    }

    public function testServe()
    {
        $server = $this->createServer();
        $request = new Request(json_encode([
            'id' => 1,
            'method' => sprintf('%s.query', fixtures\ApiService::class),
            'params' => [['query' => null]],
        ]));
        $response = $server->serve($request, new Response());
        $this->assertInstanceOf(Response::class, $response);
        $body = json_decode((string) $response->getBody(), true);
        $e = unserialize(base64_decode($body['error']['data']));
        $this->assertEquals('InvalidArgumentException', $e['class']);
        // $this->assertEquals('{"id":1,"jsonrpc":"1.0","result":[{"name":"foo"}]}', (string) $response->getBody());
    }
}
