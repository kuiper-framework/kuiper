<?php

declare(strict_types=1);

namespace kuiper\web\middleware;

abstract class AbstractMiddlewareFactory implements MiddlewareFactory
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
