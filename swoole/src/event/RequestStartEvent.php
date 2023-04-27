<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

class RequestStartEvent
{
    public function __construct(private readonly RequestEventInterface $requestEvent)
    {
    }

    public function getRequestEvent(): RequestEventInterface
    {
        return $this->requestEvent;
    }
}
