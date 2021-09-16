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

namespace kuiper\swoole;

use kuiper\swoole\constants\ServerSetting;
use PHPUnit\Framework\TestCase;

class SwooleSettingTest extends TestCase
{
    public function testEverySettingHasType()
    {
        foreach (ServerSetting::instances() as $setting) {
            $this->assertNotNull($setting->type);
        }
    }
}
