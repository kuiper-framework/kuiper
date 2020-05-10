<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

use kuiper\event\StoppableEventTrait;
use kuiper\swoole\server\ServerInterface;
use Psr\EventDispatcher\StoppableEventInterface;

abstract class AbstractServerEvent implements StoppableEventInterface
{
    use StoppableEventTrait;

    /**
     * @var ServerInterface
     */
    private $server;

    public function getServer(): ServerInterface
    {
        return $this->server;
    }

    public function setServer(ServerInterface $server): void
    {
        $this->server = $server;
    }
}
