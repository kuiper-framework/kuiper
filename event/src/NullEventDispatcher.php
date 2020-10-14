<?php

declare(strict_types=1);

namespace kuiper\event;

use Psr\EventDispatcher\EventDispatcherInterface;

class NullEventDispatcher implements EventDispatcherInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event)
    {
        return $event;
    }
}
