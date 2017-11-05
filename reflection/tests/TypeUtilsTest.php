<?php

namespace kuiper\reflection;

use kuiper\reflection\type\CompositeType;
use kuiper\reflection\type\IntegerType;

class TypeUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        $this->assertInstanceOf(IntegerType::class, TypeUtils::parse('int'));

        $this->assertInstanceOf(CompositeType::class, TypeUtils::parse('int|null'));
    }

    public function testIsArray()
    {
        $this->assertTrue(TypeUtils::isArray(ReflectionType::forName('array')));
        $this->assertTrue(TypeUtils::isArray(ReflectionType::forName('int[]')));
        $this->assertTrue(TypeUtils::isArray(ReflectionType::forName('int[][]')));
    }

    public function testIsScalar()
    {
        $this->assertTrue(TypeUtils::isScalar(ReflectionType::forName('bool')));
        $this->assertTrue(TypeUtils::isScalar(ReflectionType::forName('int')));
        $this->assertTrue(TypeUtils::isScalar(ReflectionType::forName('string')));
        $this->assertTrue(TypeUtils::isScalar(ReflectionType::forName('float')));
    }

    public function testIsCompound()
    {
        $this->assertTrue(TypeUtils::isCompound(ReflectionType::forName('array')));
        $this->assertTrue(TypeUtils::isCompound(ReflectionType::forName('object')));
        $this->assertTrue(TypeUtils::isCompound(ReflectionType::forName('callable')));
        $this->assertTrue(TypeUtils::isCompound(ReflectionType::forName('iterable')));
    }

    public function testIsPseudo()
    {
        $this->assertTrue(TypeUtils::isPseudo(ReflectionType::forName('mixed')));
        $this->assertTrue(TypeUtils::isPseudo(ReflectionType::forName('number')));
        $this->assertTrue(TypeUtils::isPseudo(ReflectionType::forName('void')));
    }

    public function testIsNull()
    {
        $this->assertTrue(TypeUtils::isNull(ReflectionType::forName('null')));
    }

    public function testIsResource()
    {
        $this->assertTrue(TypeUtils::isResource(ReflectionType::forName('resource')));
    }

    public function testIsClass()
    {
        $this->assertTrue(TypeUtils::isClass(ReflectionType::forName('\ArrayAccess')));
    }

    public function testIsPrimitive()
    {
        $this->assertFalse(TypeUtils::isPrimitive(ReflectionType::forName('\ArrayAccess')));
    }

    public function testValidate()
    {
        $this->assertTrue(TypeUtils::validate(ReflectionType::forName('int'), 1));
    }

    public function testSanitize()
    {
        $this->assertEquals(TypeUtils::sanitize(ReflectionType::forName('int'), '1.0'), 1);
    }
}
