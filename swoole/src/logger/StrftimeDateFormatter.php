<?php

declare(strict_types=1);

namespace kuiper\swoole\logger;

class StrftimeDateFormatter implements DateFormatterInterface
{
    /**
     * @var string
     */
    private $format;

    /**
     * StrftimeDateFormatter constructor.
     *
     * @param string $format
     */
    public function __construct(string $format)
    {
        $this->format = $format;
    }

    /**
     * {@inheritDoc}
     */
    public function format(float $time): string
    {
        return strftime($this->format, (int) $time);
    }
}
