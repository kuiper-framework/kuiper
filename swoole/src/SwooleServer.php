<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\swoole\event\ServerEventFactory;
use kuiper\swoole\exception\ServerStateException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Swoole\Server;

class SwooleServer implements ServerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const MASTER_PROCESS_NAME = 'master';
    public const MANAGER_PROCESS_NAME = 'manager';
    public const WORKER_PROCESS_NAME = 'worker';

    /**
     * @var ServerConfig
     */
    private $serverConfig;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Server
     */
    private $swooleServer;

    /**
     * @var ServerEventFactory
     */
    private $swooleServerEventFactory;

    public function __construct(ServerConfig $serverConfig, EventDispatcherInterface $eventDispatcher)
    {
        self::check();
        $this->serverConfig = $serverConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->swooleServerEventFactory = new ServerEventFactory($this);
    }

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
        $this->eventDispatcher->dispatch($this->swooleServerEventFactory->create('beforeStart', []));
        $ports = $this->serverConfig->getPorts();
        $this->createSwooleServer(array_shift($ports));
        foreach ($ports as $adapter) {
            $this->addPort($adapter);
        }
        $this->swooleServer->start();
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        $pids = $this->getAllPids();
        if (empty($pids)) {
            throw new ServerStateException('Server was not started');
        }
        exec('kill -9 '.implode(' ', $pids), $output, $ret);
        if (0 !== $ret) {
            throw new ServerStateException('Server was failed to stop');
        }
    }

    public function getServerConfig(): ServerConfig
    {
        return $this->serverConfig;
    }

    public function getSwooleServer(): Server
    {
        return $this->swooleServer;
    }

    public function getAllPids()
    {
        $pids[] = $this->getMasterPid();
        $pids[] = $this->getManagerPid();
        $pids = array_merge($pids, $this->getWorkerPidList());

        return array_filter($pids);
    }

    public function getMasterPid()
    {
        return current($this->getPidListByType(self::MASTER_PROCESS_NAME));
    }

    public function getManagerPid()
    {
        return current($this->getPidListByType(self::MANAGER_PROCESS_NAME));
    }

    public function getWorkerPidList()
    {
        return $this->getPidListByType(self::WORKER_PROCESS_NAME);
    }

    private function getPidListByType(string $processType): array
    {
        $cmd = sprintf("ps -ewo pid,cmd | grep %s | grep %s | grep -v grep | awk '{print $1}'",
            $this->serverConfig->getServerName(), $processType);
        exec($cmd, $pids);
        $this->logger->debug("[SwooleServer] get $processType pid list by '$cmd'", ['pid' => $pids]);

        return array_map('intval', $pids);
    }

    private function swooleEventHandler(string $eventName): callable
    {
        return function () use ($eventName) {
            $event = $this->swooleServerEventFactory->create($eventName, func_get_args());
            if ($event) {
                $this->logger->debug("[SwooleServer] create $eventName event ".get_class($event));
                $this->eventDispatcher->dispatch($event);
            } else {
                $this->logger->debug("[SwooleServer] no event handler for $eventName");
            }
        };
    }

    private function createSwooleServer(ServerPort $port): void
    {
        $serverType = $port->getServerType();
        $swooleServerClass = $serverType->server;
        $this->swooleServer = new $swooleServerClass($port->getHost(), $port->getPort(), SWOOLE_PROCESS, $port->getSockType());
        $this->swooleServer->set(array_merge($this->serverConfig->getSettings()->toArray(), $serverType->settings));

        foreach (Event::values() as $event) {
            if (in_array($event, Event::requestEvents(), true)) {
                continue;
            }
            $this->logger->debug("[SwooleServer] attach $event to server");

            $this->swooleServer->on($event, $this->swooleEventHandler($event));
        }

        foreach ($serverType->events as $event) {
            $this->logger->debug("[SwooleServer] attach $event to server");
            $this->swooleServer->on($event, $this->swooleEventHandler($event));
        }
    }

    private function addPort(ServerPort $port): void
    {
        $serverType = $port->getServerType();
        /** @var Server\Port $swoolePort */
        $swoolePort = $this->swooleServer->addlistener($port->getHost(), $port->getPort(), $port->getSockType());
        $swoolePort->set($serverType->settings);

        foreach ($serverType->events as $event) {
            $this->logger->debug("[SwooleServer] attach $event to port ".$port->getPort());
            $swoolePort->on($event, $this->swooleEventHandler($event));
        }
    }
}
