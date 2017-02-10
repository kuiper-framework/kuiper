<?php

namespace kuiper\rpc\server\middleware;

use kuiper\rpc\server\fixtures;
use kuiper\rpc\server\Request;
use kuiper\rpc\server\Response;
use kuiper\rpc\server\Server;
use kuiper\rpc\server\ServiceResolver;
use kuiper\rpc\server\TestCase;

class JsonRpcTest extends TestCase
{
    public function createServer()
    {
        $server = new Server($this->resolver = new ServiceResolver());
        $server->add(new JsonRpc());
        $this->resolver->add(new fixtures\Calculator());

        return $server;
    }

    public function testServe()
    {
        $server = $this->createServer();
        $request = new Request(json_encode([
            'id' => 1,
            'method' => sprintf('%s.add', fixtures\Calculator::class),
            'params' => [1, 2],
        ]));
        $response = $server->serve($request, new Response());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('{"id":1,"jsonrpc":"1.0","result":3}', (string) $response->getBody());
    }

    public function testMalformedJson()
    {
        $server = $this->createServer();
        $request = new Request('');
        $response = $server->serve($request, new Response());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('{"id":null,"error":{"code":-32700,"message":"Malformed json: Syntax error"}}', (string) $response->getBody());
    }
}
