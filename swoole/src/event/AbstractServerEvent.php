<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

use kuiper\swoole\server\ServerInterface;
use Psr\EventDispatcher\StoppableEventInterface;

abstract class AbstractServerEvent implements StoppableEventInterface
{
    private $propagationStopped = false;

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
