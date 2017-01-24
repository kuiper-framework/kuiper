<?php
namespace kuiper\reflection;

use kuiper\reflection\fixtures\DummyClass;

class ReflectionTypeTest extends TestCase
{
    public function testPrimitive()
    {
        $type = ReflectionType::parse('int');
        $this->assertTrue($type->isBuiltin());
        $this->assertFalse($type->isArray());
        $this->assertFalse($type->isCompound());
        $this->assertFalse($type->isObject());
        $this->assertTrue($type->validate('10'));
        $this->assertSame($type->sanitize('10'), 10);
        $this->assertEquals((string)$type, 'int');
    }

    public function testArray()
    {
        $type = ReflectionType::parse('int[]');
        $this->assertFalse($type->isBuiltin());
        $this->assertTrue($type->isArray());
        $this->assertFalse($type->isCompound());
        $this->assertFalse($type->isObject());
        $this->assertTrue($type->validate(['10']));
        $this->assertSame($type->sanitize(['10']), [10]);
        $this->assertEquals((string)$type, 'int[]');
    }

    public function testObjectType()
    {
        $type = ReflectionType::parse(DummyClass::class);
        $this->assertFalse($type->isBuiltin());
        $this->assertFalse($type->isArray());
        $this->assertFalse($type->isCompound());
        $this->assertTrue($type->isObject());
        $this->assertTrue($type->validate(new DummyClass));
        $this->assertEquals((string)$type, DummyClass::class);
    }

    public function testCompoundType()
    {
        $type = ReflectionType::parse(DummyClass::class.'|null');
        $this->assertFalse($type->isBuiltin());
        $this->assertFalse($type->isArray());
        $this->assertTrue($type->isCompound());
        $this->assertFalse($type->isObject());
        $this->assertTrue($type->validate(new DummyClass));
        $this->assertTrue($type->validate(null));
        $this->assertEquals((string)$type, DummyClass::class.'|null');
    }
}
