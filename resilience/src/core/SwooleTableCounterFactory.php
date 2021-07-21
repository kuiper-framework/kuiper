<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

use Swoole\Table;

class SwooleTableCounterFactory extends AbstractCounterFactory
{
    /**
     * @var Table
     */
    private $table;

    public function __construct(int $size = 1024)
    {
        $this->table = new Table($size);
        $this->table->column(SwooleTableCounter::COLUMN, Table::TYPE_INT);
        $this->table->create();
    }

    protected function createInternal(string $name): Counter
    {
        return new SwooleTableCounter($this->table, $name);
    }
}
