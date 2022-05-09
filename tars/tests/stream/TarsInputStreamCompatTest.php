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

use kuiper\helper\Arrays;
use kuiper\tars\fixtures\Request;
use kuiper\tars\fixtures\RequestWithDefault;
use kuiper\tars\type\PrimitiveType;
use kuiper\tars\type\TypeParser;
use PHPUnit\Framework\TestCase;

class TarsInputStreamCompatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('phptars')) {
            $this->markTestSkipped(
                'The phptars extension is not available.'
            );
        }
    }

    public function testOptional()
    {
        $request = new Request();
        $typeParser = new TypeParser();
        $type = $typeParser->parse('Request', 'kuiper\\tars\\fixtures');
        $data = TarsOutputStream::pack($type, $request);
        /** @var Request $obj */
        $obj = TarsInputStream::unpack($type, $data);
        $this->assertNull($obj->boolOpt);
        $this->assertNull($obj->stringOpt);
        $this->assertNull($obj->intOpt);
    }

    public function testInt()
    {
        $request = new Request();
        $request->intOpt = -10;
        $typeParser = new TypeParser(AnnotationReader::getInstance());
        $type = $typeParser->parse('Request', 'kuiper\\tars\\fixtures');
        $data = TarsOutputStream::pack($type, $request);
        /** @var Request $obj */
        $obj = TarsInputStream::unpack($type, $data);
        $this->assertEquals(-10, $obj->intOpt);
    }

    public function testLong()
    {
        $request = new Request();
        $request->longRequired = -2147483647;

        $typeParser = new TypeParser(AnnotationReader::getInstance());
        $type = $typeParser->parse('Request', 'kuiper\\tars\\fixtures');
        $data = TarsOutputStream::pack($type, $request);
        /** @var Request $obj */
        $obj = TarsInputStream::unpack($type, $data);
        $this->assertEquals($request->longRequired, $obj->longRequired);
    }

    /**
     * @dataProvider intNumbers
     */
    public function testIntBound(int $num)
    {
        $type = PrimitiveType::long();
        $os = TarsOutputStream::pack($type, $num);
        $payload = \TUPAPI::putInt64((string) 0, $num, 3);
        $is = new TarsInputStream($payload);
        var_export($payload);
        var_export($is->tokenize());
        $is = new TarsInputStream($os);
        var_export($is->tokenize());
        $this->assertEquals($os, $payload);

        $value = TarsInputStream::unpack($type, $os);
        $this->assertEquals($value, $num);
    }

    public function testFloat()
    {
        $num = 1.2;
        $os = TarsOutputStream::pack(PrimitiveType::float(), $num);
        $payload = \TUPAPI::putFloat((string) 0, $num, 3);
        $is = new TarsInputStream($payload);
        var_export($payload);
        var_export($is->tokenize());
        $is = new TarsInputStream($os);
        var_export($is->tokenize());
        $this->assertEquals($os, $payload);
    }

    public function testDouble()
    {
        $num = 1.2;
        $os = TarsOutputStream::pack(PrimitiveType::double(), $num);
        $payload = \TUPAPI::putDouble((string) 0, $num, 3);
        $is = new TarsInputStream($payload);
        var_export($payload);
        var_export($is->tokenize());
        $is = new TarsInputStream($os);
        var_export($is->tokenize());
        $this->assertEquals($os, $payload);
    }

    public function testInt1()
    {
        $num = TarsConst::MIN_INT32 - 1;
        $type = PrimitiveType::long();
        $os = TarsOutputStream::pack($type, $num);
        $payload = \TUPAPI::putInt64((string) 0, $num, 3);
        $is = new TarsInputStream($payload);

        $buffer = self::toPayload('', $payload);
        $ret = \TUPAPI::getInt64('', $buffer, false, 3);
        var_export([$ret]);

        var_export($payload);
        var_export($is->tokenize());
        $is = new TarsInputStream($os);
        var_export($is->tokenize());
        $this->assertEquals($os, $payload);
        $value = TarsInputStream::unpack($type, $os);
        $this->assertEquals($num, $value);
    }

    public static function toPayload(string $name, string $payload): string
    {
        $requestBuf = \TUPAPI::encode(3, 1, '',
            '', 0, 0, 0,
            [], [], [$name => $payload]);
        $decodeRet = \TUPAPI::decode($requestBuf);

        return $decodeRet['sBuffer'];
    }

    public function intNumbers(): array
    {
        return Arrays::flatten(array_map(function ($num) {
            return [[$num], [$num + 1], [$num - 1]];
        }, [TarsConst::MIN_INT8,
            TarsConst::MAX_INT8,
            TarsConst::MIN_INT16,
            TarsConst::MIN_INT16,
            TarsConst::MAX_INT16,
            TarsConst::MIN_INT32,
            TarsConst::MAX_INT32, ]));
    }

    public function testDefaultValue()
    {
        $request = new Request();
        $request->intOpt = -10;
        $typeParser = new TypeParser(AnnotationReader::getInstance());
        $type = $typeParser->parse('Request', 'kuiper\\tars\\fixtures');
        $data = TarsOutputStream::pack($type, $request);

        $type = $typeParser->parse('RequestWithDefault', 'kuiper\\tars\\fixtures');
        /** @var RequestWithDefault $obj */
        $obj = TarsInputStream::unpack($type, $data);
        $this->assertEquals([], $obj->arrayOpt);
    }

    public function testMapVector()
    {
        $typeParser = new TypeParser(AnnotationReader::getInstance());
        $type = $typeParser->parse('map<string,vector<string>>', '');
        $data = TarsOutputStream::pack($type, ['a' => ['b']]);

        $ret = TarsInputStream::unpack($type, $data);
        var_export($ret);
    }
}
