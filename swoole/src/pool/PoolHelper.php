<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

class PoolHelper
{
    public static function call(PoolInterface $pool, callable $call): mixed
    {
        $conn = $pool->take();
        try {
            return $call($conn);
        } finally {
            $pool->release($conn);
        }
    }
}
