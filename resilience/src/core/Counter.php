<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

interface Counter
{
    public function get(): int;

    public function set(int $value): void;

    public function increment(int $value = 1): int;

    public function decrement(int $value = 1): int;
}
