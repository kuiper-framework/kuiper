<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\tars\fixtures\HelloService;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\TarsOutputStream;
use kuiper\tars\type\MapType;
use kuiper\tars\type\PrimitiveType;
use PHPUnit\Framework\TestCase;

class TarsServerMethodFactoryTest extends TestCase
{
    public function testCreate()
    {
        $serverProperties = new ServerProperties();
        $serverProperties->setApp('app');
        $serverProperties->setServer('server');

        $factory = new TarsServerMethodFactory($serverProperties, [
            'app.server.HelloObj' => \Mockery::mock(HelloService::class),
        ]);
        $requestPacket = new RequestPacket();
        $requestPacket->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), [
            'name' => TarsOutputStream::pack(PrimitiveType::string(), 'world'),
        ]);
        $method = $factory->create('app.server.HelloObj', 'hello', [$requestPacket]);
        // print_r($method);
        $this->assertEquals(['world'], $method->getArguments());
    }
}
