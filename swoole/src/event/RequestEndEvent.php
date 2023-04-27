<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

use Throwable;

class RequestEndEvent
{
    public function __construct(private readonly RequestEventInterface $requestEvent, private readonly ?Throwable $error = null)
    {
    }

    public function getRequestEvent(): RequestEventInterface
    {
        return $this->requestEvent;
    }

    /**
     * @return Throwable|null
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }
}
