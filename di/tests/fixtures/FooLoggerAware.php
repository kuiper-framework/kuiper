<?php

declare(strict_types=1);

namespace kuiper\di\fixtures;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class FooLoggerAware implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
