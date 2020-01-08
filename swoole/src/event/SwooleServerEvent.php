<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

use kuiper\swoole\ServerInterface;
use Psr\EventDispatcher\StoppableEventInterface;

abstract class SwooleServerEvent implements StoppableEventInterface
{
    private $propagationStopped = false;

    /**
     * @var ServerInterface
     */
    private $server;

    /**
     * @var \Swoole\Server
     */
    private $swooleServer;

    public function getServer(): ServerInterface
    {
        return $this->server;
    }

    public function setServer(ServerInterface $server): void
    {
        $this->server = $server;
    }

    public function setSwooleServer(\Swoole\Server $swooleServer): void
    {
        $this->swooleServer = $swooleServer;
    }

    public function getSwooleServer(): \Swoole\Server
    {
        return $this->swooleServer;
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     *
     * If multiple event listeners are connected to the same event, no
     * further event listener will be triggered once any trigger calls
     * stopPropagation().
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
