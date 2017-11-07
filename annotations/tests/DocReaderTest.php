<?php

namespace kuiper\annotations;

use kuiper\annotations\fixtures\DummyClass;
use kuiper\annotations\fixtures\PhpDocVarType;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\TypeUtils;
use ReflectionClass;

class DocReaderTest extends TestCase
{
    public function createReader()
    {
        return new DocReader();
    }

    protected function getProperty($name)
    {
        $class = new ReflectionClass(PhpDocVarType::class);

        return $class->getProperty($name);
    }

    protected function getMethod($name)
    {
        $class = new ReflectionClass(PhpDocVarType::class);

        return $class->getMethod($name);
    }

    /**
     * @dataProvider varTypes
     */
    public function testGetPropertyType($property, $type)
    {
        $reader = $this->createReader();
        $this->assertEquals(
            (string) $reader->getPropertyType($this->getProperty($property)),
            $type
        );
    }

    public function testGetPropertyClass()
    {
        $reader = $this->createReader();
        $this->assertNull($reader->getPropertyClass($this->getProperty('integer')));
        $this->assertEquals(
            $reader->getPropertyClass($this->getProperty('annotation')),
            DummyClass::class
        );
    }

    /**
     * @dataProvider methodTypes
     */
    public function testGetParamTypes($method, $types)
    {
        $reader = $this->createReader();
        $params = $reader->getParameterTypes($this->getMethod($method));
        // var_export($params);
        $this->assertEquals(array_map(function ($type) {
            return (string) $type;
        }, $params), $types);
    }

    public function testGetMethodClass()
    {
        $reader = $this->createReader();
        $this->assertEquals(
            $reader->getParameterClasses($this->getMethod('integerMethod')),
            []
        );
        $this->assertEquals(
            $reader->getParameterClasses($this->getMethod('annotMethod')),
            ['annot' => DummyClass::class]
        );
    }

    public function testGetInheritDoc()
    {
        $reader = $this->createReader();
        $types = $reader->getParameterTypes($this->getMethod('foo'));
        // print_r($types);
        $this->assertEquals((string) $types['i'], 'int');

        $type = $reader->getReturnType($this->getMethod('bar'));
        $this->assertEquals((string) $type, 'int');
    }

    public function testMethodParams()
    {
        $reader = $this->createReader();
        $class = new ReflectionClass(fixtures\DocMethodParams::class);
        $types = $reader->getParameterTypes($class->getMethod('setValues'));
        // print_r($types);
        /** @var ArrayType $type */
        $type = $types['values'];
        $this->assertTrue(TypeUtils::isArray($type));
        $this->assertEquals(fixtures\DummyClass::class, $type->getValueType()->getName());
    }

    public function methodTypes()
    {
        return [
            ['integerMethod', [
                'integer' => 'int',
            ]],
            ['annotMethod', [
                'annot' => DummyClass::class,
                'bool' => 'bool',
            ]],
        ];
    }

    public function varTypes()
    {
        return [
            ['mixed', 'mixed'],
            ['boolean', 'bool'],
            ['bool', 'bool'],
            ['float', 'float'],
            ['string', 'string'],
            ['integer', 'int'],
            ['array', 'array'],
            ['annotation', DummyClass::class],
            ['arrayOfIntegers', 'int[]'],
            ['arrayOfStrings', 'string[]'],
            ['arrayOfAnnotations', DummyClass::class.'[]'],
            ['multipleType', 'string|array'],
        ];
    }
}
