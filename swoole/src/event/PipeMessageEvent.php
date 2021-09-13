<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

class PipeMessageEvent extends AbstractServerEvent
{
    /**
     * @var int
     */
    private $fromWorkerId;

    /**
     * @var MessageInterface
     */
    private $message;

    public function getFromWorkerId(): int
    {
        return $this->fromWorkerId;
    }

    public function setFromWorkerId(int $fromWorkerId): void
    {
        $this->fromWorkerId = $fromWorkerId;
    }

    /**
     * @return MessageInterface
     */
    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    /**
     * @param MessageInterface $message
     */
    public function setMessage(MessageInterface $message): void
    {
        $this->message = $message;
    }
}
