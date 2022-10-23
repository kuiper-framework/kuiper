<?php

declare(strict_types=1);

namespace kuiper\swoole\constants;

use PHPUnit\Framework\TestCase;

class ClientSettingsTest extends TestCase
{
    public function testName(): void
    {
        $settings = ClientSettings::OPEN_EOF_CHECK;
        $this->assertEquals('bool', ClientSettings::type($settings));
    }

    public function testDefinitionMatch(): void
    {
        $refl = new \ReflectionClass(ClientSettings::class);
        foreach ($refl->getConstants() as $key => $value) {
            $this->assertEquals($key, strtoupper($value));
            $this->assertEquals($value, strtolower($key));
        }
    }
}
