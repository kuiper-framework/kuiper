<?php

declare(strict_types=1);

namespace kuiper\swoole\exception;

use kuiper\swoole\pool\PoolInterface;

class PoolTimeoutException extends \Exception
{
    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * PoolTimeoutException constructor.
     */
    public function __construct(PoolInterface $pool)
    {
        parent::__construct('Cannot get connection because pool is exausted');
        $this->pool = $pool;
    }

    public function getPool(): PoolInterface
    {
        return $this->pool;
    }
}
