<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

trait PoolTrait
{
    public function with(callable $callback)
    {
        try {
            $conn = $this->take();

            return $callback($conn);
        } finally {
            if ($conn) {
                $this->release($conn);
            }
        }
    }
}
