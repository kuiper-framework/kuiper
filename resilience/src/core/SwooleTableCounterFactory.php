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
