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

namespace kuiper\rpc\client;

use kuiper\rpc\fixtures\UserService;
use PHPUnit\Framework\TestCase;

class ProxyGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $generator = new ProxyGenerator();
        $result = $generator->generate(UserService::class);
        $this->assertNotNull($result->getCode());
        //echo $result->getCode();
        $this->assertEquals(file_get_contents(dirname(__DIR__).'/fixtures/UserServiceProxy.txt'), $result->getCode());
    }
}
