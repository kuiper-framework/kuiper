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

namespace kuiper\tars\stream;

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
        $typeParser = new TypeParser();
        $type = $typeParser->parse('vector<EndpointF>', 'kuiper\\tars\\integration');
        $result = TarsInputStream::unpack($type, $ret['']);
        // print_r($result);
        $this->assertIsArray($result);
        $this->assertInstanceOf(EndpointF::class, $result[0]);
    }

    public function testData()
    {
        $data = file_get_contents(__DIR__.'/../fixtures/struct.data');
        $typeParser = new TypeParser();
        $type = $typeParser->parse('vector<User>', 'kuiper\\tars\\fixtures');
        $result = TarsInputStream::unpack($type, $data);
        $this->assertIsArray($result);
    }
}
