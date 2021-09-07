<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class EndpointTest extends TestCase
{
    public function testFromUriWithPort()
    {
        $uri = new Uri('tcp://server:18000?connectTimeout=100&receiveTimeout=300');
        $endpoint = Endpoint::fromUri($uri);
        var_export($endpoint);
        $this->assertEquals('server', $endpoint->getHost());
    }

    public function testFromUriWithoutPort()
    {
        $uri = new Uri('tcp://server?connectTimeout=100&receiveTimeout=300');
        $endpoint = Endpoint::fromUri($uri);
        var_export($endpoint);
        $this->assertEquals('server', $endpoint->getHost());
    }

    public function testFromUriHttp()
    {
        $uri = new Uri('http://server?connectTimeout=100&receiveTimeout=300');
//        var_export([
//            $uri->getHost(),
//            $uri->getPort()
//        ]);
        $endpoint = Endpoint::fromUri($uri);
        // var_export($endpoint);
        $this->assertEquals('server', $endpoint->getHost());
    }

    public function testFromPartial()
    {
        $uri = new Uri('/?connectTimeout=100&receiveTimeout=300');
//        var_export([
//            $uri->getHost(),
//            $uri->getPort()
//        ]);
        $endpoint = Endpoint::fromUri($uri);
        var_export($endpoint);
        $this->assertEquals('', $endpoint->getHost());
    }
}
