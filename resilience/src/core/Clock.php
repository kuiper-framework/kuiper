<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

interface Clock
{
    public function getEpochSecond(): int;

    public function getTimeInMillis(): int;

    public function sleep(int $millis): void;
}
