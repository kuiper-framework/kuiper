<?php

declare(strict_types=1);

namespace kuiper\db;

interface TransactionManagerInterface
{
    /**
     * @param callable $callback the callback
     *
     * @return mixed
     */
    public function transaction(callable $callback);
}
