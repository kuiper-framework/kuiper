<?php

declare(strict_types=1);

namespace kuiper\swoole\logger;

interface DateFormatterInterface
{
    /**
     * @param float $time
     *
     * @return string
     */
    public function format(float $time): string;
}
