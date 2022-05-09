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

namespace kuiper\tars\server\stat;

use Iterator;
use kuiper\tars\integration\StatMicMsgBody;
use Swoole\Table;

class SwooleTableStatStore implements StatStore
{
    public const KEY_COUNT = 'count';
    public const KEY_TIMEOUT_COUNT = 'timeoutCount';
    public const KEY_EXEC_COUNT = 'execCount';
    public const KEY_TOTAL_RSP_TIME = 'totalRspTime';
    public const KEY_MAX_RSP_TIME = 'maxRspTime';
    public const KEY_MIN_RSP_TIME = 'minRspTime';
    public const KEY_ENTRY = 'entry';

    protected readonly Table $statTable;

    protected readonly Table $keyTable;

    public function __construct(int $size = 4096)
    {
        $table = new Table($size);

        $table->column(self::KEY_COUNT, Table::TYPE_INT, 4);
        $table->column(self::KEY_TIMEOUT_COUNT, Table::TYPE_INT, 4);
        $table->column(self::KEY_EXEC_COUNT, Table::TYPE_INT, 4);
        $table->column(self::KEY_TOTAL_RSP_TIME, Table::TYPE_INT, 4);
        $table->column(self::KEY_MAX_RSP_TIME, Table::TYPE_INT, 4);
        $table->column(self::KEY_MIN_RSP_TIME, Table::TYPE_INT, 4);
        $table->create();

        $keyTable = new Table($size);
        $keyTable->column(self::KEY_ENTRY, Table::TYPE_STRING, 256);
        $keyTable->create();
        $this->statTable = $table;
        $this->keyTable = $keyTable;
    }

    public function save(StatEntry $entry): void
    {
        $key = $this->getKey($entry->getUniqueId());
        $body = $entry->getBody();
        if ($body->count > 0) {
            $this->statTable->incr($key, self::KEY_COUNT, $body->count);
        } elseif ($body->execCount > 0) {
            $this->statTable->incr($key, self::KEY_EXEC_COUNT, $body->execCount);
        } elseif ($body->timeoutCount > 0) {
            $this->statTable->incr($key, self::KEY_TIMEOUT_COUNT, $body->timeoutCount);
        }
        $this->statTable->incr($key, self::KEY_TOTAL_RSP_TIME, $body->totalRspTime);

        $this->statTable->set($key, [
            self::KEY_MAX_RSP_TIME => max($body->maxRspTime, $this->statTable->get($key, self::KEY_MAX_RSP_TIME)),
            self::KEY_MIN_RSP_TIME => min($body->minRspTime, $this->statTable->get($key, self::KEY_MIN_RSP_TIME)),
        ]);
    }

    public function delete(StatEntry $entry): void
    {
        $key = $this->getKey($entry->getUniqueId(), false);
        $this->statTable->del($key);
        $this->keyTable->del($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntries(int $maxIndex): Iterator
    {
        foreach ($this->statTable as $key => $row) {
            $data = $this->getEntry($key);
            if (empty($data)) {
                continue;
            }
            $entry = StatEntry::fromString($data);
            if ($entry->getIndex() < $maxIndex) {
                $body = get_object_vars($entry->getBody());
                foreach ($row as $name => $value) {
                    $body[$name] = $value;
                }

                yield $entry->withBody(new StatMicMsgBody(...$body));
            }
        }
    }

    private function getKey(string $entry, bool $create = true): string
    {
        $key = md5($entry);
        if ($create && !$this->keyTable->exist($key)) {
            $this->keyTable->set($key, [self::KEY_ENTRY => $entry]);
        }

        return $key;
    }

    private function getEntry(string $key): string
    {
        return (string) $this->keyTable->get($key, self::KEY_ENTRY);
    }
}
