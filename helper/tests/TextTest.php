<?php

declare(strict_types=1);

namespace kuiper\helper;

class TextTest extends TestCase
{
    /**
     * @dataProvider camelizeCase
     */
    public function testCamelize($str, $delimiter, $expect)
    {
        $this->assertEquals($expect, Text::camelCase($str, $delimiter));
    }

    public function camelizeCase()
    {
        return [
            ['coco_bongo', null, 'CocoBongo'],
            ['co_co-bon_go', '-', 'Co_coBon_go'],
            ['co_co-bon_go', '-_', 'CoCoBonGo'],
        ];
    }

    /**
     * @dataProvider uncamelizeCase
     */
    public function testUncamelize($str, $delimiter, $expect)
    {
        $this->assertEquals($expect, Text::snakeCase($str, $delimiter));
    }

    public function notestUncamelizeSingle()
    {
        echo Text::snakeCase('orders_1y');
    }

    public function uncamelizeCase()
    {
        return [
            ['CocoBongo', null, 'coco_bongo'],
            ['CocoBongo', '-', 'coco-bongo'],
            ['orders1y', null, 'orders1y'],
            ['coco_bongo', null, 'coco_bongo'],
            ['orders_1y', null, 'orders_1y'],
        ];
    }

    /**
     * @dataProvider startsWithCase
     */
    public function testStartsWith($str, $needle, $case, $expect)
    {
        $this->assertEquals($expect, Text::startsWith($str, $needle, $case));
    }

    public function startsWithCase()
    {
        return [
            ['Hello', 'He', true, true],
            ['Hello', 'he', false, false],
            ['Hello', 'he', true, true],
        ];
    }

    /**
     * @dataProvider endsWithCase
     */
    public function testEndsWith($str, $needle, $case, $expect)
    {
        $this->assertEquals($expect, Text::endsWith($str, $needle, $case));
    }

    public function endsWithCase()
    {
        return [
            ['Hello', 'llo', true, true],
            ['Hello', 'LLO', false, false],
            ['Hello', 'LLO', true, true],
        ];
    }

    public function testLower()
    {
        $this->assertEquals('hello', Text::lower('HELLO'));
    }

    public function testUpper()
    {
        $this->assertEquals('HELLO', Text::upper('hello'));
    }

    public function testIsEmpty()
    {
        $this->assertTrue(Text::isEmpty(null));
        $this->assertTrue(Text::isEmpty(''));
        $this->assertFalse(Text::isEmpty('0'));
    }

    public function testIsNotEmpty()
    {
        $this->assertFalse(Text::isNotEmpty(null));
        $this->assertFalse(Text::isNotEmpty(''));
        $this->assertTrue(Text::isNotEmpty('0'));
    }
}
