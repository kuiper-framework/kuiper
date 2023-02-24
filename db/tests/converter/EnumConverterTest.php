<?php

namespace kuiper\db\converter;

use kuiper\db\fixtures\Gender;
use kuiper\db\fixtures\Student;
use kuiper\db\metadata\MetaModelFactory;
use kuiper\db\metadata\NamingStrategy;
use kuiper\reflection\ReflectionDocBlockFactory;
use PHPUnit\Framework\TestCase;

class EnumConverterTest extends TestCase
{
    public function testConvert()
    {
        $factory = new MetaModelFactory(
            AttributeConverterRegistry::createDefault(),
            new NamingStrategy(),
            ReflectionDocBlockFactory::getInstance()
        );
        $metaModel = $factory->create(Student::class);
        $columns = $metaModel->getColumns();
        $this->assertCount(2, $columns);

        $enumConverter = new EnumConverter();
        $dbData = $enumConverter->convertToDatabaseColumn(Gender::FEMALE, $columns[1]);
        $this->assertEquals('FEMALE', $dbData);

        $val = $enumConverter->convertToEntityAttribute('FEMALE', $columns[1]);
        $this->assertEquals(Gender::FEMALE, $val);

        $val = $enumConverter->convertToEntityAttribute('LBT', $columns[1]);
        $this->assertNull($val);
    }

}
