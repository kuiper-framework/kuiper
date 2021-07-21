<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class EndpointTest extends TestCase
{
    public function testFromUri()
    {
        $uri = new Uri('tcp://server:18000?connectTimeout=100&receiveTimeout=300');
        $endpoint = Endpoint::fromUri($uri);
    }
}
