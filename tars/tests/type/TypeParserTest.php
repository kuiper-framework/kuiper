<?php

namespace kuiper\tars\type;

use kuiper\tars\exception\SyntaxErrorException;
use kuiper\tars\fixtures\Request;
use PHPUnit\Framework\TestCase;

class TypeParserTest extends TestCase
{
    public function testFromPhpPrimitiveType()
    {
        $func = function (): bool {};
        $type = (new \ReflectionFunction($func))->getReturnType();
        $typeParser = new TypeParser();
        $ret = $typeParser->fromPhpType($type);
        $this->assertEquals(PrimitiveType::bool(), $ret);
    }

    public function testFromPhpWrongType()
    {
        $this->expectException(SyntaxErrorException::class);
        $func = function (): array {};
        $type = (new \ReflectionFunction($func))->getReturnType();
        $typeParser = new TypeParser();
        $ret = $typeParser->fromPhpType($type);
    }

    public function testFromPhpClassType()
    {
        $func = function (): Request {};
        $type = (new \ReflectionFunction($func))->getReturnType();
        $typeParser = new TypeParser();
        $ret = $typeParser->fromPhpType($type);
        $this->assertInstanceOf(StructType::class, $ret);
        $this->assertEquals(Request::class, $ret->getClassName());
    }
}