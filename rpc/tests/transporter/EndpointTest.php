<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class EndpointTest extends TestCase
{
    public function testFromUriWithPort(): void
    {
        $uri = new Uri('tcp://server:18000?connectTimeout=100&receiveTimeout=300');
        $endpoint = Endpoint::fromUri($uri);
       // var_export($endpoint);
        $this->assertEquals('server', $endpoint->getHost());
    }

    public function testFromUriWithoutPort(): void
    {
        $uri = new Uri('tcp://server?connectTimeout=100&receiveTimeout=300');
        $endpoint = Endpoint::fromUri($uri);
        // var_export($endpoint);
        $this->assertEquals('server', $endpoint->getHost());
    }

    public function testFromUriHttp(): void
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

    public function testFromPartial(): void
    {
        $uri = new Uri('/?connectTimeout=100&receiveTimeout=300');
//        var_export([
//            $uri->getHost(),
//            $uri->getPort()
//        ]);
        $endpoint = Endpoint::fromUri($uri);
        // var_export($endpoint);
        $this->assertEquals('', $endpoint->getHost());
    }
}
