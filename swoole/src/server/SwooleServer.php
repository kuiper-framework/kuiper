<?php

declare(strict_types=1);

namespace kuiper\swoole\server;

use kuiper\swoole\Event;
use kuiper\swoole\ServerPort;
use Swoole\Server;

class SwooleServer extends AbstractServer
{
    private const TAG = '['.__CLASS__.'] ';

    /**
     * @var Server
     */
    private $resource;

    public static function check(): void
    {
        if (!extension_loaded('swoole')) {
            throw new \RuntimeException('extension swoole should be enabled');
        }
        if (!class_exists(Server::class)) {
            throw new \RuntimeException('swoole.use_namespace should be enabled');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        $this->dispatch(Event::BOOTSTRAP, []);
        $ports = $this->getServerConfig()->getPorts();
        $this->createSwooleServer(array_shift($ports));
        foreach ($ports as $adapter) {
            $this->addPort($adapter);
        }
        $this->resource->start();
    }

    private function createSwooleServer(ServerPort $port): void
    {
        $serverType = $port->getServerType();
        $swooleServerClass = $serverType->server;
        $this->resource = new $swooleServerClass($port->getHost(), $port->getPort(), SWOOLE_PROCESS, $port->getSockType());
        $this->resource->set(array_merge($this->getSettings()->toArray(), $serverType->settings));

        foreach (Event::values() as $event) {
            if (in_array($event, Event::requestEvents(), true)) {
                continue;
            }
            $this->attach($event);
        }

        foreach ($serverType->events as $event) {
            $this->attach($event);
        }
    }

    private function addPort(ServerPort $port): void
    {
        $serverType = $port->getServerType();
        /** @var Server\Port $swoolePort */
        $swoolePort = $this->resource->addListener($port->getHost(), $port->getPort(), $port->getSockType());
        $swoolePort->set($serverType->settings);

        foreach ($serverType->events as $event) {
            $this->logger->debug(self::TAG."attach $event to port ".$port->getPort());
            $swoolePort->on($event, $this->createEventHandler($event));
        }
    }

    private function createEventHandler(string $eventName): callable
    {
        return function () use ($eventName) {
            $this->dispatch($eventName, func_get_args());
        };
    }

    /**
     * @param $event
     */
    private function attach(string $event): void
    {
        $this->logger->debug(self::TAG."attach $event to server");
        $this->resource->on($event, $this->createEventHandler($event));
    }
}
