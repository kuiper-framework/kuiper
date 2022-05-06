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

namespace kuiper\rpc\servicediscovery;

use PHPUnit\Framework\TestCase;

class ServiceEndpointTest extends TestCase
{
    public function testFromString(): void
    {
        $endpoint = ServiceEndpoint::fromString('a@tcp://localhost:9000');
        $this->assertEquals('default/a:1.0@tcp://localhost:9000?', (string) $endpoint);
    }
}
