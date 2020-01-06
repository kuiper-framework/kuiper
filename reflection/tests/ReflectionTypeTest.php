<?php

namespace kuiper\reflection;

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
}
