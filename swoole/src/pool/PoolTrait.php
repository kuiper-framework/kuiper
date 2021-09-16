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
