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

namespace kuiper\swoole\exception;

use kuiper\swoole\pool\PoolInterface;

class PoolTimeoutException extends \Exception
{
    private PoolInterface $pool;

    /**
     * PoolTimeoutException constructor.
     */
    public function __construct(PoolInterface $pool)
    {
        parent::__construct("Cannot get connection because pool {$pool->getName()} is exhausted");
        $this->pool = $pool;
    }

    public function getPool(): PoolInterface
    {
        return $this->pool;
    }
}
