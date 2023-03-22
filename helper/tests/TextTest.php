<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\helper;

class TextTest extends TestCase
{
    /**
     * @dataProvider camelizeCase
     */
    public function testCamelize($str, $delimiter, $expect): void
    {
        $this->assertEquals($expect, Text::camelCase($str, $delimiter));
    }

    public static function camelizeCase(): array
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
    public function testUncamelize($str, $delimiter, $expect): void
    {
        $this->assertEquals($expect, Text::snakeCase($str, $delimiter));
    }

    public static function uncamelizeCase(): array
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
    public function testStartsWith($str, $needle, $case, $expect): void
    {
        $this->assertEquals($expect, Text::startsWith($str, $needle, $case));
    }

    public static function startsWithCase(): array
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
    public function testEndsWith($str, $needle, $case, $expect): void
    {
        $this->assertEquals($expect, Text::endsWith($str, $needle, $case));
    }

    public static function endsWithCase(): array
    {
        return [
            ['Hello', 'llo', true, true],
            ['Hello', 'LLO', false, false],
            ['Hello', 'LLO', true, true],
        ];
    }

    public function testLower(): void
    {
        $this->assertEquals('hello', Text::lower('HELLO'));
    }

    public function testUpper(): void
    {
        $this->assertEquals('HELLO', Text::upper('hello'));
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue(Text::isEmpty(null));
        $this->assertTrue(Text::isEmpty(''));
        $this->assertFalse(Text::isEmpty('0'));
    }

    public function testIsNotEmpty(): void
    {
        $this->assertFalse(Text::isNotEmpty(null));
        $this->assertFalse(Text::isNotEmpty(''));
        $this->assertTrue(Text::isNotEmpty('0'));
    }
}
