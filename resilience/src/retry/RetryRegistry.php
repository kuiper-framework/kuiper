<?php

declare(strict_types=1);

namespace kuiper\resilience\retry;

interface RetryRegistry
{
    public function get(string $name): ?Retry;

    public function register(string $name, Retry $retry): void;
}
