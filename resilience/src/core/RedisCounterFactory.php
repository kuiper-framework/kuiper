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

namespace kuiper\resilience\core;

use Redis;

class RedisCounterFactory extends AbstractCounterFactory
{
    public function __construct(private readonly Redis $redis)
    {
    }

    protected function createInternal(string $name): Counter
    {
        return new RedisCounter($this->redis, $name);
    }
}
