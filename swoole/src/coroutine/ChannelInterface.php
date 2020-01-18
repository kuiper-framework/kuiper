<?php

declare(strict_types=1);

namespace kuiper\swoole\coroutine;

interface ChannelInterface
{
    /**
     * @param mixed $data
     * @param float $timeout
     */
    public function push($data, float $timeout = null): bool;

    /**
     * @param float $timeout
     *
     * @return mixed|false return false if not available
     */
    public function pop(float $timeout = null);

    public function size(): int;
}
