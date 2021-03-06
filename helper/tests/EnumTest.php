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
    public function testEquality()
    {
        $on = OnOff::fromValue('1');
        $onObj = OnOff::ON();
        // var_export([$on, $onObj]);
        $this->assertTrue($on === $onObj);
    }

    public function testName()
    {
        $this->assertEquals(Gender::MALE()->name(), 'MALE');
    }

    public function testValue()
    {
        $this->assertEquals(Gender::MALE()->value(), 'm');
    }

    public function testToString()
    {
        $this->assertEquals((string) Gender::MALE(), 'MALE');
    }

    public function testMagicGet()
    {
        $this->assertEquals(Gender::MALE()->name, 'MALE');
        $this->assertEquals(Gender::MALE()->value, 'm');
        $this->assertEquals(Gender::MALE()->description, '男');
    }

    public function testValues()
    {
        $this->assertEquals(Gender::values(), ['m', 'f']);
    }

    public function testNames()
    {
        $this->assertEquals(Gender::names(), ['MALE', 'FEMALE']);
    }

    public function testInstances()
    {
        $this->assertEquals(Gender::instances(), [
            Gender::MALE(), Gender::FEMALE(),
        ]);
    }

    public function testNameOf()
    {
        $this->assertEquals(Gender::nameOf('m'), 'MALE');
    }

    public function testHasName()
    {
        $this->assertTrue(Gender::hasName('MALE'));
    }

    public function testValueOf()
    {
        $this->assertEquals(Gender::valueOf('MALE'), 'm');
    }

    public function testHasValue()
    {
        $this->assertTrue(Gender::hasValue('m'));
    }

    public function testFromName()
    {
        $this->assertEquals(Gender::fromName('MALE'), Gender::MALE());
    }

    public function testFromValue()
    {
        $this->assertEquals(Gender::fromValue('m'), Gender::MALE());
    }

    public function testJsonSerialize()
    {
        $this->assertEquals('["MALE","MALE"]',
                            json_encode([Gender::fromValue('m'), Gender::MALE()]));
    }

    public function testOrdinal()
    {
        $this->assertEquals(OnOff::ON()->ordinal(), 0);
        $this->assertEquals(OnOff::OFF()->ordinal(), 1);
    }

    public function testGetPropertyNotDefined()
    {
        $this->expectException(\InvalidArgumentException::class);
        Gender::MALE()->text;
    }

    public function testGetPropertyAbsent()
    {
        $this->assertNull(Gender::FEMALE()->enName);
    }
}
