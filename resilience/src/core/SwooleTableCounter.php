<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

use Swoole\Table;

class SwooleTableCounter implements Counter
{
    public const COLUMN = 'value';

    /**
     * @var Table
     */
    private $table;

    /**
     * @var string
     */
    private $key;

    /**
     * SwooleTableCounter constructor.
     */
    public function __construct(Table $table, string $key)
    {
        $this->table = $table;
        $this->key = $key;
    }

    public function increment(int $value = 1): int
    {
        return $this->table->incr($this->key, self::COLUMN, $value);
    }

    public function get(): int
    {
        return $this->table->get($this->key, self::COLUMN);
    }

    public function set(int $value): void
    {
        $this->table->set($this->key, [self::COLUMN => $value]);
    }

    public function decrement(int $value = 1): int
    {
        return $this->table->decr($this->key, self::COLUMN, $value);
    }
}
