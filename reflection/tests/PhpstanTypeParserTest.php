<?php

declare(strict_types=1);

namespace kuiper\reflection;

use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\IntegerType;
use kuiper\reflection\type\MapType;
use kuiper\reflection\type\StringType;
use PHPUnit\Framework\TestCase;

class PhpstanTypeParserTest extends TestCase
{
    /**
     * @dataProvider types
     */
    public function testName(string $typeString, ReflectionTypeInterface $type)
    {
        $parser = new PhpstanTypeParser();

        $this->assertEquals($type, $parser->parse($typeString));
        $this->assertEquals($typeString, (string) $type);
    }

    public static function types()
    {
        return [
            [
                'string', new StringType(),
            ],
            [
                'string[]', new ArrayType(new StringType()),
            ],
            [
                'array<int, string[]>', new MapType(new IntegerType(), new ArrayType(new StringType())),
            ],
        ];
    }
}
