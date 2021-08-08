<?php

declare(strict_types=1);

namespace kuiper\tars\server\monitor\collector;

use kuiper\tars\server\monitor\MetricPolicy;

interface CollectorInterface
{
    /**
     * @see MetricPolicy
     *
     * @return string
     */
    public function getPolicy(): string;

    /**
     * @return array
     */
    public function getValues(): array;
}
