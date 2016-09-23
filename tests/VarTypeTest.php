<?php
namespace kuiper\reflection;

use kuiper\test\TestCase;
use kuiper\reflection\fixtures\DummyClass;

class VarTypeTest extends TestCase
{
    public function testPrimitive()
    {
        $type = VarType::integer();
        $this->assertTrue($type->isPrimitive());
        $this->assertFalse($type->isArray());
        $this->assertFalse($type->isMulitiple());
        $this->assertFalse($type->isObjectType());
        $this->assertTrue($type->validate('10'));
        $this->assertSame($type->sanitize('10'), 10);
        $this->assertEquals((string)$type, 'integer');
    }

    public function testArray()
    {
        $type = VarType::arrayType(VarType::integer());
        $this->assertFalse($type->isPrimitive());
        $this->assertTrue($type->isArray());
        $this->assertFalse($type->isMulitiple());
        $this->assertFalse($type->isObjectType());
        $this->assertTrue($type->validate(['10']));
        $this->assertSame($type->sanitize(['10']), [10]);
        $this->assertEquals((string)$type, 'array<integer>');
    }

    public function testObjectType()
    {
        $type = VarType::objectType(DummyClass::class);
        $this->assertFalse($type->isPrimitive());
        $this->assertFalse($type->isArray());
        $this->assertFalse($type->isMulitiple());
        $this->assertTrue($type->isObjectType());
        $this->assertTrue($type->validate(new DummyClass));
        $this->assertEquals((string)$type, DummyClass::class);
    }

    public function testMultipleType()
    {
        $type = VarType::multipleType([VarType::objectType(DummyClass::class), VarType::null()]);
        $this->assertFalse($type->isPrimitive());
        $this->assertFalse($type->isArray());
        $this->assertTrue($type->isMulitiple());
        $this->assertFalse($type->isObjectType());
        $this->assertTrue($type->validate(new DummyClass));
        $this->assertTrue($type->validate(null));
        $this->assertEquals((string)$type, DummyClass::class.'|null');
    }
}