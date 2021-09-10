<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use Swoole\Table;

class SwooleTableStateStore implements StateStore
{
    private const STATE = 'state';
    private const OPEN_AT = 'open';

    /**
     * @var Table
     */
    private $table;

    public function __construct(int $size = 1024)
    {
        $this->table = new Table($size);
        $this->table->column(self::STATE, Table::TYPE_INT);
        $this->table->column(self::OPEN_AT, Table::TYPE_INT);
        $this->table->create();
    }

    public function getState(string $name): State
    {
        $value = $this->table->get($name, self::STATE);
        if (false !== $value && State::hasValue($value)) {
            return State::fromValue($value);
        }

        return State::CLOSED();
    }

    public function setState(string $name, State $state): void
    {
        $value = [
            self::STATE => $state->value,
        ];
        if (State::OPEN === $state->value) {
            $value[self::OPEN_AT] = (int) (microtime(true) * 1000);
        }
        $this->table->set($name, $value);
    }

    public function getOpenAt(string $name): int
    {
        $value = $this->table->get($name);
        if (State::OPEN === $value[self::STATE]) {
            return $value[self::OPEN_AT];
        }

        return 0;
    }
}
