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

namespace kuiper\swoole\constants;

use PHPUnit\Framework\TestCase;

class SwooleSettingTest extends TestCase
{
    public function testEverySettingHasType(): void
    {
        $this->assertTrue(ServerSetting::has('package_body_offset'));
    }

    public function testDefinitionMatch(): void
    {
        $refl = new \ReflectionClass(ServerSetting::class);
        foreach ($refl->getConstants() as $key => $value) {
            $this->assertEquals($key, strtoupper($value));
            $this->assertEquals($value, strtolower($key));
        }
    }
}
