<?php

declare(strict_types=1);

namespace kuiper\tars\stream;

use kuiper\annotations\AnnotationReader;
use kuiper\tars\integration\EndpointF;
use kuiper\tars\type\MapType;
use kuiper\tars\type\TypeParser;
use PHPUnit\Framework\TestCase;

class ResponsePacketTest extends TestCase
{
    public function testName()
    {
        $packet = ResponsePacket::decode(file_get_contents(__DIR__.'/../fixtures/response.data'));
        $is = new TarsInputStream($packet->sBuffer);
        $ret = $is->readMap(0, true, MapType::byteArrayMap());
        $typeParser = new TypeParser(AnnotationReader::getInstance());
        $type = $typeParser->parse('vector<EndpointF>', 'kuiper\\tars\\integration');
        $result = TarsInputStream::unpack($type, $ret['']);
        // print_r($result);
        $this->assertIsArray($result);
        $this->assertInstanceOf(EndpointF::class, $result[0]);
    }
}
