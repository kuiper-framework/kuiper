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

use kuiper\reflection\fixtures\ReflectionTypes;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\IntegerType;

class ReflectionTypeTest extends TestCase
{
    /**
     * @dataProvider scalarTypes
     */
    public function testType($typeString, $typeName = null)
    {
        $type = ReflectionType::forName($typeString);
        $this->assertEquals((string) $type, $typeName ?: $typeString);
    }

    public function scalarTypes()
    {
        return [
            ['bool'],
            ['int'],
            ['string'],
            ['float'],
            ['resource'],
            ['void'],
            ['object'],
            ['null'],
            ['callable'],
            ['mixed'],
            ['array'],
            ['?bool'],
            ['?int'],
            ['?string'],
            ['?float'],
            ['?resource'],
            ['?object'],
            ['?callable'],
            ['?array'],
            // alias
            ['integer', 'int'],
            ['boolean', 'bool'],
            ['double', 'float'],
            ['callback', 'callable'],
            ['?integer', '?int'],
            ['?boolean', '?bool'],
            ['?double', '?float'],
            ['?callback', '?callable'],
        ];
    }

    public function testParseArray()
    {
        /** @var ArrayType $type */
        $type = ReflectionType::forName('int[][]');
        $this->assertInstanceOf(ArrayType::class, $type);
        $this->assertEquals(2, $type->getDimension());
        $this->assertInstanceOf(IntegerType::class, $type->getValueType());
    }

    public function testParseNullableArray()
    {
        /** @var ArrayType $type */
        $type = ReflectionType::forName('?int[][]');
        $this->assertInstanceOf(ArrayType::class, $type);
        $this->assertEquals(2, $type->getDimension());
        $this->assertInstanceOf(IntegerType::class, $type->getValueType());
        $this->assertTrue($type->allowsNull());
    }

    public function testPhpArrayType()
    {
        $prop = new \ReflectionProperty(ReflectionTypes::class, 'arrayOpt');
        $type = $prop->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertTrue($type->allowsNull());
        $this->assertEquals('?array', (string) $type);
    }

    public function testPhpUnionType()
    {
        $prop = new \ReflectionProperty(ReflectionTypes::class, 'union');
        $type = $prop->getType();
        $this->assertInstanceOf(\ReflectionUnionType::class, $type);
        $this->assertTrue($type->allowsNull());
        $this->assertEquals('string|float|null', (string) $type);
        $this->assertEquals(['string', 'float', 'null'], array_map(static function (\ReflectionType $t) {
            return (string) $t;
        }, $type->getTypes()));
    }
}
