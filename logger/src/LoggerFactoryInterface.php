<?php

declare(strict_types=1);

namespace kuiper\logger;

use Psr\Log\LoggerInterface;

interface LoggerFactoryInterface
{
    /**
     * Creates logger for the class.
     */
    public function create(string $className): LoggerInterface;
}
