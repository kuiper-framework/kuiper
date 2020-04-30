<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

class CloseEvent extends AbstractServerEvent
{
    /**
     * @var int
     */
    private $fd;
    /**
     * @var int
     */
    private $reactorId;

    public function getFd(): int
    {
        return $this->fd;
    }

    public function setFd(int $fd): void
    {
        $this->fd = $fd;
    }

    public function getReactorId(): int
    {
        return $this->reactorId;
    }

    public function setReactorId(int $reactorId): void
    {
        $this->reactorId = $reactorId;
    }
}
