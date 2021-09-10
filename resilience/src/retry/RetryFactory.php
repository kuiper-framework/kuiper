<?php

declare(strict_types=1);

namespace kuiper\resilience\retry;

interface RetryFactory
{
    /**
     * @param string $name
     *
     * @return Retry
     */
    public function create(string $name): Retry;
}
