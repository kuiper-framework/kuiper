<?php

namespace kuiper\rpc\server;

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
        $request = (new Request(''))
                 ->withMethod(sprintf('%s.add', fixtures\Calculator::class))
                 ->withParameters([1, 2]);
        $response = $server->serve($request, new Response());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(3, $response->getResult());
    }
}
