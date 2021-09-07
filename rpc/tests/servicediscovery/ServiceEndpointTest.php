<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery;

use PHPUnit\Framework\TestCase;

class ServiceEndpointTest extends TestCase
{
    public function testFromString()
    {
        $endpoint = ServiceEndpoint::fromString('a@tcp://localhost:9000');
        $this->assertEquals('default/a:1.0@tcp://localhost:9000?', (string) $endpoint);
    }
}
