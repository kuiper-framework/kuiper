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

namespace kuiper\swoole\coroutine;

use PHPUnit\Framework\TestCase;
use Swoole\Coroutine as SwooleCoroutine;

class CoroutineTest extends TestCase
{
    public function testGetContext()
    {
        SwooleCoroutine::create(function () {
            $context = SwooleCoroutine::getContext();
            $this->assertInstanceOf(SwooleCoroutine\Context::class, $context);
            $this->assertInstanceOf(\ArrayObject::class, $context);
            echo 'Coroutine#'.SwooleCoroutine::getCid().' exit'.PHP_EOL;
        });
    }
}
