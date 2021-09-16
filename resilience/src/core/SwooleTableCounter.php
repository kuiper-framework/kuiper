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
        $value = $this->table->get($this->key, self::COLUMN);

        return false === $value ? 0 : $value;
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
