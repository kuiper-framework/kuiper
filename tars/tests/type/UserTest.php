<?php

declare(strict_types=1);

namespace kuiper\tars\type;

use kuiper\tars\fixtures\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testName()
    {
        $obj = new User(id: 10);
        // var_export($obj);
        $this->assertEquals(10, $obj->id);
    }

    public function testParse()
    {
        $parser = new TypeParser();
        $type = $parser->parse('User', 'kuiper\tars\fixtures');
        $this->assertInstanceOf(StructType::class, $type);
    }
}
