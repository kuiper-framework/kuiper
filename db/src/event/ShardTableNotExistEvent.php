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

namespace kuiper\db\event;

use kuiper\db\sharding\StatementInterface;
use kuiper\event\StoppableEventTrait;
use Psr\EventDispatcher\StoppableEventInterface;

class ShardTableNotExistEvent implements StoppableEventInterface
{
    use StoppableEventTrait;

    private bool $tableCreated = false;

    public function __construct(private readonly StatementInterface $statement,
                                private readonly string $table)
    {
    }

    public function getStatement(): StatementInterface
    {
        return $this->statement;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function isTableCreated(): bool
    {
        return $this->tableCreated;
    }

    public function setTableCreated(bool $tableCreated): void
    {
        $this->tableCreated = $tableCreated;
    }
}
