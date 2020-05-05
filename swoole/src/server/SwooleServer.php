<?php

declare(strict_types=1);

namespace kuiper\swoole\server;

use kuiper\swoole\ConnectionInfo;
use kuiper\swoole\constants\Event;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\event\RequestEvent;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use kuiper\swoole\http\SwooleResponseBridgeInterface;
use kuiper\swoole\ServerPort;
use Swoole\Server;

class SwooleServer extends AbstractServer
{
    protected const TAG = '['.__CLASS__.'] ';

    private const SWOOLE_SERVER_WORKER = '__SwooleServer:worker';

    /**
     * @var HttpMessageFactoryHolder
     */
    private $httpMessageFactoryHolder;

    /**
     * @var SwooleRequestBridgeInterface
     */
    private $swooleRequestBridge;

    /**
     * @var SwooleResponseBridgeInterface
     */
    private $swooleResponseBridge;

    /**
     * @var Server
     */
    private $resource;

    /**
     * @var Server
     */
    private $workerServer;

    public function getHttpMessageFactoryHolder(): HttpMessageFactoryHolder
    {
        return $this->httpMessageFactoryHolder;
    }

    public function setHttpMessageFactoryHolder(HttpMessageFactoryHolder $httpMessageFactoryHolder): void
    {
        $this->httpMessageFactoryHolder = $httpMessageFactoryHolder;
    }

    public function getSwooleRequestBridge(): SwooleRequestBridgeInterface
    {
        return $this->swooleRequestBridge;
    }

    public function setSwooleRequestBridge(SwooleRequestBridgeInterface $swooleRequestBridge): void
    {
        $this->swooleRequestBridge = $swooleRequestBridge;
    }

    public function getSwooleResponseBridge(): SwooleResponseBridgeInterface
    {
        return $this->swooleResponseBridge;
    }

    public function setSwooleResponseBridge(SwooleResponseBridgeInterface $swooleResponseBridge): void
    {
        $this->swooleResponseBridge = $swooleResponseBridge;
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
        self::check();
        $this->dispatch(Event::BOOTSTRAP, []);
        $ports = $this->getServerConfig()->getPorts();
        $this->createSwooleServer(array_shift($ports));
        foreach ($ports as $adapter) {
            $this->addPort($adapter);
        }
        $this->resource->start();
    }

    public function reload(): void
    {
        $this->resource->reload();
    }

    public function stop(): void
    {
        $this->resource->stop();
    }

    public function task($data, $taskWorkerId = -1, $onFinish = null)
    {
        return $this->resource->task($data, $taskWorkerId, $onFinish);
    }

    public function finish($data): void
    {
        $this->resource->finish($data);
    }

    public function getMasterPid(): int
    {
        return $this->resource->master_pid;
    }

    public function isTaskWorker(): bool
    {
        return $this->hasWorker() && (bool) $this->getWorker()->taskworker;
    }

    public function send(int $clientId, string $data): void
    {
        if ($this->getWorker()) {
            $this->getWorker()->send($clientId, $data);
        }
    }

    public function getConnectionInfo(int $clientId): ?ConnectionInfo
    {
        $clientInfo = $this->resource->getClientInfo($clientId);
        if (empty($clientInfo)) {
            return null;
        }
        $connectionInfo = new ConnectionInfo();
        $connectionInfo->setRemoteIp((string) ($clientInfo['remote_ip'] ?? ''));
        $connectionInfo->setRemotePort((int) ($clientInfo['remote_port'] ?? 0));
        $connectionInfo->setServerPort((int) ($clientInfo['server_port'] ?? 0));
        $connectionInfo->setServerFd((int) ($clientInfo['server_fd'] ?? 0));
        $connectionInfo->setConnectTime((int) ($clientInfo['connect_time'] ?? 0));
        $connectionInfo->setLastTime((int) ($clientInfo['last_time'] ?? 0));

        return $connectionInfo;
    }

    private function createSwooleServer(ServerPort $port): void
    {
        $serverType = ServerType::fromValue($port->getServerType());
        $swooleServerClass = $serverType->server;
        $this->resource = new $swooleServerClass($port->getHost(), $port->getPort(), SWOOLE_PROCESS, $port->getSockType());
        $this->resource->set(array_merge($this->getSettings()->toArray(), $serverType->settings));

        foreach (Event::values() as $event) {
            if (Event::fromValue($event)->non_swoole
                || in_array($event, Event::requestEvents(), true)) {
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
        $serverType = ServerType::fromValue($port->getServerType());
        /** @var Server\Port $swoolePort */
        $swoolePort = $this->resource->addListener($port->getHost(), $port->getPort(), $port->getSockType());
        $swoolePort->set($serverType->settings);

        foreach ($serverType->events as $event) {
            $this->logger->debug(static::TAG."attach $event to port ".$port->getPort());
            $swoolePort->on($event, $this->createEventHandler($event));
        }
    }

    private function createEventHandler(string $eventName): callable
    {
        return function () use ($eventName) {
            $args = func_get_args();
            if (Event::REQUEST === $eventName) {
                $this->onRequest(...$args);

                return;
            }
            if ($args[0] instanceof Server) {
                $this->setWorker(array_shift($args));
            }

            try {
                $this->dispatch($eventName, $args);
            } finally {
                $this->setWorker(null);
            }
        };
    }

    private function setWorker(?Server $server): void
    {
        $this->workerServer = $server;
    }

    private function hasWorker(): bool
    {
        return isset($this->workerServer);
    }

    private function getWorker(): ?Server
    {
        return $this->workerServer;
    }

    private function attach(string $event): void
    {
        $this->logger->debug(static::TAG."attach $event to server");
        $this->resource->on($event, $this->createEventHandler($event));
    }

    private function onRequest($request, $response): void
    {
        try {
            /** @var RequestEvent $event */
            $event = $this->dispatch(Event::REQUEST, [$this->swooleRequestBridge->create($request)]);
            if ($event) {
                $this->swooleResponseBridge->update($event->getResponse() ?? $this->httpMessageFactoryHolder->getResponseFactory()
                        ->createResponse(500), $response);
            }
        } catch (\Exception $e) {
            $this->logger->error(static::TAG.'handle http request failed: '.$e->getMessage());
            $this->swooleResponseBridge->update($event->getResponse() ?? $this->httpMessageFactoryHolder->getResponseFactory()
                    ->createResponse(500), $response);
        }
    }
}
