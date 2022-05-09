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

use kuiper\tars\fixtures\Request;
use kuiper\tars\fixtures\RequestWithDefault;
use kuiper\tars\type\Type;
use kuiper\tars\type\TypeParser;
use PHPUnit\Framework\TestCase;

class TarsInputStreamTest extends TestCase
{

    public function testOptional(): void
    {
        $request = new Request(
            intRequired: 32
        );
        $type = $this->createType();
        $data = TarsOutputStream::pack($type, $request);
        /** @var Request $obj */
        $obj = TarsInputStream::unpack($type, $data);
        // var_export($obj);
        $this->assertEquals(32, $obj->intRequired);
        $this->assertNull($obj->boolOpt);
        $this->assertNull($obj->stringOpt);
        $this->assertNull($obj->intOpt);
    }

    public function testInt(): void
    {
        $request = new Request(
            intOpt: -10
        );
        $type = $this->createType();
        $data = TarsOutputStream::pack($type, $request);
        /** @var Request $obj */
        $obj = TarsInputStream::unpack($type, $data);
        $this->assertEquals(-10, $obj->intOpt);
    }

    public function testLong(): void
    {
        $request = new Request(
            longRequired: -2147483647
        );

        $type = $this->createType();
        $data = TarsOutputStream::pack($type, $request);
        /** @var Request $obj */
        $obj = TarsInputStream::unpack($type, $data);
        $this->assertEquals($request->longRequired, $obj->longRequired);
    }

    public function testDefaultValue(): void
    {
        $request = new Request(
            intOpt: -10
        );
        $type = $this->createType();
        $data = TarsOutputStream::pack($type, $request);
        // var_export((new TarsInputStream($data))->tokenize());

        $typeParser = new TypeParser();
        $type = $typeParser->parse('RequestWithDefault', 'kuiper\\tars\\fixtures');
        /** @var RequestWithDefault $obj */
        $obj = TarsInputStream::unpack($type, $data);
        $this->assertNull($obj->arrayOpt);
    }

    public function testMapVector(): void
    {
        $typeParser = new TypeParser();
        $type = $typeParser->parse('map<string,vector<string>>', '');
        $arr = ['a' => ['b']];
        $data = TarsOutputStream::pack($type, $arr);

        $ret = TarsInputStream::unpack($type, $data);
        // var_export($ret);
        $this->assertEquals($arr, $ret);
    }

    private function createType(): Type
    {
        return (new TypeParser())->parse('Request', 'kuiper\\tars\\fixtures');
    }
}
