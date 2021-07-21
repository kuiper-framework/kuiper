<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

use kuiper\resilience\circuitbreaker\SlideWindowType;

interface MetricsFactory
{
    public function create(string $name, SlideWindowType $type, int $windowSize): Metrics;
}
