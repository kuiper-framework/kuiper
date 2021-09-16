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
