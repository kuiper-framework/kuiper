<?php

declare(strict_types=1);

namespace kuiper\event\async;

use kuiper\swoole\task\AbstractTask;

class AsyncEventTask extends AbstractTask
{
    /**
     * @var object
     */
    private $event;

    /**
     * AsyncEventTask constructor.
     *
     * @param object $event
     */
    public function __construct(object $event)
    {
        $this->event = $event;
    }

    /**
     * @return object
     */
    public function getEvent(): object
    {
        return $this->event;
    }
}
