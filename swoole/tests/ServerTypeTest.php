<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\swoole\constants\ServerType;
use PHPUnit\Framework\TestCase;

class ServerTypeTest extends TestCase
{
    public function testAllServerTypeHasSettings()
    {
        foreach (ServerType::instances() as $serverType) {
            $this->assertIsArray($serverType->settings);
        }
    }

    public function testAllServerTypeHasEvents()
    {
        foreach (ServerType::instances() as $serverType) {
            $this->assertIsArray($serverType->events);
        }
    }
}
