<?php

declare(strict_types=1);

namespace kuiper\web\annotation\filter;

abstract class AbstractFilter implements FilterInterface
{
    /**
     * @var int
     */
    public $priority = 1024;

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
