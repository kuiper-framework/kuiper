<?php

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
