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

namespace kuiper\tars;

use kuiper\swoole\constants\ServerType;
use kuiper\swoole\ServerConfig;
use kuiper\swoole\ServerPort;
use PHPUnit\Framework\TestCase;

class ServerConfigTest extends TestCase
{
    public function testConstruct()
    {
        $config = new ServerConfig('test', [
            new ServerPort('0', 80, ServerType::HTTP),
            new ServerPort('0', 70, ServerType::TCP),
        ]);
        $this->assertEquals(80, $config->getPort()->getPort());

        $config = new ServerConfig('test', [
            new ServerPort('0', 70, ServerType::TCP),
            new ServerPort('0', 80, ServerType::HTTP),
        ]);
        $this->assertEquals(80, $config->getPort()->getPort());
    }
}
