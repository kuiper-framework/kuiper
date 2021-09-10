<?php

declare(strict_types=1);

namespace kuiper\rpc;

abstract class AbstractMiddlewareFactory implements MiddlewareFactoryInterface
{
    /**
     * @var int
     */
    public $priority = 1024;

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
