<?php

declare(strict_types=1);

namespace kuiper\helper;

use PHPUnit\Framework\TestCase;

class IpUtilTest extends TestCase
{
    public static function ipProvider()
    {
        return [
            ['192.16.0.123', '192.16.0.0/24', true],
            ['192.16.1.123', '192.16.0.0/24', false],
            ['192.16.1.123', '192.16.0.0/16', true],
            ['192.16.0.123', ['172.16.0.0/24', '192.16.0.0/24'], true],
        ];
    }

    /**
     * @dataProvider ipProvider
     *
     * @return void
     */
    public function testCidrMatch(string $ip, string|array $range, bool $match)
    {
        $this->assertEquals(IpUtil::cidrMatch($ip, $range), $match);
    }
}
