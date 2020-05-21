<?php

declare(strict_types=1);

namespace kuiper\db;

interface TransactionManagerInterface
{
    /**
     * @return mixed
     */
    public function transaction(callable $callback);
}
