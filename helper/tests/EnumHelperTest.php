<?php

declare(strict_types=1);

namespace kuiper\helper;

use InvalidArgumentException;
use kuiper\helper\fixtures\EnumGender;
use PHPUnit\Framework\TestCase;

class EnumHelperTest extends TestCase
{
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
