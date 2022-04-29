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

use kuiper\helper\fixtures\Gender;
use kuiper\helper\fixtures\OnOff;

/**
 * TestCase for Enum.
 */
class EnumTest extends TestCase
{
    public function testEquality(): void
    {
        $on = OnOff::fromValue('1');
        $onObj = OnOff::ON();
        // var_export([$on, $onObj]);
        $this->assertSame($on, $onObj);
    }

    public function testName(): void
    {
        $this->assertEquals('MALE', Gender::MALE()->name());
    }

    public function testValue(): void
    {
        $this->assertEquals('m', Gender::MALE()->value());
    }

    public function testToString(): void
    {
        $this->assertEquals('MALE', (string) Gender::MALE());
    }

    public function testMagicGet(): void
    {
        $this->assertEquals('MALE', Gender::MALE()->name);
        $this->assertEquals('m', Gender::MALE()->value);
        $this->assertEquals('男', Gender::MALE()->description);
    }

    public function testValues(): void
    {
        $this->assertEquals(['m', 'f'], Gender::values());
    }

    public function testNames(): void
    {
        $this->assertEquals(['MALE', 'FEMALE'], Gender::names());
    }

    public function testInstances(): void
    {
        $this->assertEquals([
            Gender::MALE(), Gender::FEMALE(),
        ], Gender::instances());
    }

    public function testNameOf(): void
    {
        $this->assertEquals('MALE', Gender::nameOf('m'));
    }

    public function testHasName(): void
    {
        $this->assertTrue(Gender::hasName('MALE'));
    }

    public function testValueOf(): void
    {
        $this->assertEquals('m', Gender::valueOf('MALE'));
    }

    public function testHasValue(): void
    {
        $this->assertTrue(Gender::hasValue('m'));
    }

    public function testFromName(): void
    {
        $this->assertEquals(Gender::fromName('MALE'), Gender::MALE());
    }

    public function testFromValue(): void
    {
        $this->assertEquals(Gender::fromValue('m'), Gender::MALE());
    }

    public function testJsonSerialize(): void
    {
        $this->assertEquals('["MALE","MALE"]',
                            json_encode([Gender::fromValue('m'), Gender::MALE()]));
    }

    public function testOrdinal(): void
    {
        $this->assertEquals(0, OnOff::ON()->ordinal());
        $this->assertEquals(1, OnOff::OFF()->ordinal());
    }

    public function testGetPropertyNotDefined(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Gender::MALE()->text;
    }

    public function testGetPropertyAbsent(): void
    {
        $this->assertNull(Gender::FEMALE()->enName);
    }
}
