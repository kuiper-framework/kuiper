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

namespace kuiper\tars\server;

use kuiper\annotations\AnnotationReader;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocator;
use kuiper\swoole\ServerPort;
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

        $factory = new TarsServerMethodFactory('app.server.HelloObj', $this->createServices([
            'app.server.HelloObj' => \Mockery::mock(HelloService::class),
        ]), AnnotationReader::getInstance());
        $requestPacket = new RequestPacket();
        $requestPacket->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), [
            'name' => TarsOutputStream::pack(PrimitiveType::string(), 'world'),
        ]);
        $method = $factory->create('app.server.HelloObj', 'hello', [$requestPacket]);
        // print_r($method);
        $this->assertEquals(['world'], $method->getArguments());
    }

    public function createServices(array $services): array
    {
        $ret = [];
        foreach ($services as $name => $impl) {
            $ret[$name] = new Service(
                new ServiceLocator($name),
                $impl,
                [],
                new ServerPort('localhost', 0, 'tcp')
            );
        }

        return $ret;
    }
}
