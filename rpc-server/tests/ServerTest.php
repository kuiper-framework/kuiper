<?php

namespace kuiper\rpc\server;

use kuiper\rpc\Request;
use kuiper\rpc\Response;
use kuiper\rpc\ResponseInterface;

class ServerTest extends TestCase
{
    public function createServer()
    {
        $this->resolver = new ServiceResolver();
        $this->resolver->add(new fixtures\Calculator());

        return new Server($this->resolver);
    }

    public function testServe()
    {
        $server = $this->createServer();
        $request = (new Request('', sprintf('%s.add', fixtures\Calculator::class), [1, 2]));
        $response = $server->serve($request, new Response());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(3, $response->getResult());
    }
}
