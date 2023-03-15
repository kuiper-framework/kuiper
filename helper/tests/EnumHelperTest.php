<?php

declare(strict_types=1);

namespace kuiper\helper;

use InvalidArgumentException;
use kuiper\helper\fixtures\EnumGender;
use kuiper\helper\fixtures\EnumStatus;
use PHPUnit\Framework\TestCase;

class EnumHelperTest extends TestCase
{
    public function testTryFrom()
    {
        $ret = EnumHelper::tryFrom(EnumGender::class, 'm');
        $this->assertEquals(EnumGender::MALE, $ret);
    }

    public function testTryFromInt()
    {
        // $ret = EnumStatus::tryFrom("0");
        $ret = EnumHelper::tryFrom(EnumStatus::class, '0');
        $this->assertEquals(EnumStatus::WAIT, $ret);
    }

    public function testTryFromName()
    {
        $ret = EnumHelper::tryFromName(EnumGender::class, 'MALE');
        $this->assertEquals(EnumGender::MALE, $ret);
    }

    public function testTryFromNameNotFound()
    {
        $ret = EnumHelper::tryFromName(EnumGender::class, 'male');
        $this->assertNull($ret);
    }

    public function testFromName()
    {
        $ret = EnumHelper::fromName(EnumGender::class, 'MALE');
        $this->assertEquals(EnumGender::MALE, $ret);
    }

    public function testFromNameNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $ret = EnumHelper::fromName(EnumGender::class, 'male');
    }
}
