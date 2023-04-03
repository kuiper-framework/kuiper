<?php

declare(strict_types=1);

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace kuiper\reflection;

use kuiper\reflection\type\CompositeType;
use kuiper\reflection\type\IntegerType;

class TypeTest extends TestCase
{
    public function testParse()
    {
        $this->assertInstanceOf(IntegerType::class, ReflectionType::parse('int'));
        $this->assertInstanceOf(CompositeType::class, ReflectionType::parse('int|float|null'));
    }

    public function testIsArray()
    {
        $this->assertTrue(ReflectionType::parse('array')->isArray());
        $this->assertTrue(ReflectionType::parse('int[]')->isArray());
        $this->assertTrue(ReflectionType::parse('int[][]')->isArray());
    }

    public function testIsScalar()
    {
        $this->assertTrue(ReflectionType::parse('bool')->isScalar());
        $this->assertTrue(ReflectionType::parse('int')->isScalar());
        $this->assertTrue(ReflectionType::parse('string')->isScalar());
        $this->assertTrue(ReflectionType::parse('float')->isScalar());
    }

    public function testIsCompound()
    {
        $this->assertTrue(ReflectionType::parse('array')->isCompound());
        $this->assertTrue(ReflectionType::parse('object')->isCompound());
        $this->assertTrue(ReflectionType::parse('callable')->isCompound());
        $this->assertTrue(ReflectionType::parse('iterable')->isCompound());
    }

    public function testIsPseudo()
    {
        $this->assertTrue(ReflectionType::parse('mixed')->isPseudo());
        $this->assertTrue(ReflectionType::parse('number')->isPseudo());
        $this->assertTrue(ReflectionType::parse('void')->isPseudo());
    }

    public function testIsNull()
    {
        $this->assertTrue(ReflectionType::parse('null')->isNull());
    }

    public function testIsResource()
    {
        $this->assertTrue(ReflectionType::parse('resource')->isResource());
    }

    public function testIsClass()
    {
        $this->assertTrue(ReflectionType::parse('\ArrayAccess')->isClass());
    }

    public function testIsPrimitive()
    {
        $this->assertFalse(ReflectionType::parse('\ArrayAccess')->isPrimitive());
    }

    public function testValidate()
    {
        $this->assertTrue(ReflectionType::parse('int')->isValid(1));
    }

    public function testSanitize()
    {
        $this->assertEquals(ReflectionType::parse('int')->sanitize('1.0'), 1);
    }
}
