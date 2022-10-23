<?php

declare(strict_types=1);

namespace kuiper\tars\type;

use kuiper\tars\fixtures\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testName()
    {
        $obj = new Request(
            intRequired: 1,
            boolRequired: false,
            boolOpt: false,
            stringRequired: 'abc',
            longRequired: 2
        );
        // var_export($obj);
        $this->assertFalse($obj->boolOpt);
    }

    public function testParse()
    {
        $parser = new TypeParser();
        $type = $parser->parse('Request', 'kuiper\tars\fixtures');
        $this->assertInstanceOf(StructType::class, $type);
    }
}
