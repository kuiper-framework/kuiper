<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

enum MetricType: string
{
    case COUNTER = 'counter';
    case GAUGE = 'gauge';
    case TIMER = 'timer';
}
