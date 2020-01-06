<?php

namespace kuiper\reflection;

use kuiper\reflection\type\CompositeType;
use kuiper\reflection\type\IntegerType;

class TypeTest extends TestCase
{
    public function testParse()
    {
        $this->assertInstanceOf(IntegerType::class, ReflectionType::parse('int'));
        $this->assertInstanceOf(CompositeType::class, ReflectionType::parse('int|null'));
    }

    public function testIsArray()
    {
        $this->assertTrue(ReflectionType::forName('array')->isArray());
        $this->assertTrue(ReflectionType::forName('int[]')->isArray());
        $this->assertTrue(ReflectionType::forName('int[][]')->isArray());
    }

    public function testIsScalar()
    {
        $this->assertTrue(ReflectionType::forName('bool')->isScalar());
        $this->assertTrue(ReflectionType::forName('int')->isScalar());
        $this->assertTrue(ReflectionType::forName('string')->isScalar());
        $this->assertTrue(ReflectionType::forName('float')->isScalar());
    }

    public function testIsCompound()
    {
        $this->assertTrue(ReflectionType::forName('array')->isCompound());
        $this->assertTrue(ReflectionType::forName('object')->isCompound());
        $this->assertTrue(ReflectionType::forName('callable')->isCompound());
        $this->assertTrue(ReflectionType::forName('iterable')->isCompound());
    }

    public function testIsPseudo()
    {
        $this->assertTrue(ReflectionType::forName('mixed')->isPseudo());
        $this->assertTrue(ReflectionType::forName('number')->isPseudo());
        $this->assertTrue(ReflectionType::forName('void')->isPseudo());
    }

    public function testIsNull()
    {
        $this->assertTrue(ReflectionType::forName('null')->isNull());
    }

    public function testIsResource()
    {
        $this->assertTrue(ReflectionType::forName('resource')->isResource());
    }

    public function testIsClass()
    {
        $this->assertTrue(ReflectionType::forName('\ArrayAccess')->isClass());
    }

    public function testIsPrimitive()
    {
        $this->assertFalse(ReflectionType::forName('\ArrayAccess')->isPrimitive());
    }

    public function testValidate()
    {
        $this->assertTrue(ReflectionType::forName('int')->isValid(1));
    }

    public function testSanitize()
    {
        $this->assertEquals(ReflectionType::forName('int')->sanitize('1.0'), 1);
    }
}
