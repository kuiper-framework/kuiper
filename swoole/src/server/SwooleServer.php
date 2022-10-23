<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\swoole\server;

use Exception;
use kuiper\swoole\ConnectionInfo;
use kuiper\swoole\constants\Event;
use kuiper\swoole\event\MessageInterface;
use kuiper\swoole\event\RequestEvent;
use kuiper\swoole\http\HttpMessageFactoryHolder;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use kuiper\swoole\http\SwooleResponseBridgeInterface;
use kuiper\swoole\ServerPort;
use Psr\Http\Message\ResponseFactoryInterface;
use RuntimeException;
use Swoole\Server;

class SwooleServer extends AbstractServer
{
    protected const TAG = '['.__CLASS__.'] ';

    private ?HttpMessageFactoryHolder $httpMessageFactoryHolder = null;

    private ?SwooleRequestBridgeInterface $swooleRequestBridge = null;

    private ?SwooleResponseBridgeInterface $swooleResponseBridge = null;

    private ?Server $resource = null;

    public function getHttpMessageFactoryHolder(): HttpMessageFactoryHolder
    {
        return $this->httpMessageFactoryHolder;
    }

    public function setHttpMessageFactoryHolder(?HttpMessageFactoryHolder $httpMessageFactoryHolder): void
    {
        $this->httpMessageFactoryHolder = $httpMessageFactoryHolder;
    }

    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->getHttpMessageFactoryHolder()->getResponseFactory();
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
            throw new RuntimeException('extension swoole should be enabled');
        }
        if (!class_exists(Server::class)) {
            throw new RuntimeException('swoole.use_namespace should be enabled');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        self::check();
        $this->dispatch(Event::BOOTSTRAP->value, []);
        $ports = $this->getServerConfig()->getPorts();
        $this->createSwooleServer(array_shift($ports));
        foreach ($ports as $adapter) {
            $this->addPort($adapter);
        }
        $this->resource->start();
    }

    /**
     * {@inheritdoc}
     */
    public function reload(): void
    {
        $this->dispatch(Event::BEFORE_RELOAD->value, []);
        $this->resource->reload();
        $this->dispatch(Event::AFTER_RELOAD->value, []);
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        $this->resource->stop();
    }

    /**
     * {@inheritdoc}
     */
    public function task($data, $taskWorkerId = -1, $onFinish = null)
    {
        return $this->resource->task($data, $taskWorkerId, $onFinish);
    }

    /**
     * {@inheritdoc}
     */
    public function finish($data): void
    {
        $this->resource->finish($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getMasterPid(): int
    {
        return $this->resource->master_pid;
    }

    /**
     * {@inheritdoc}
     */
    public function isTaskWorker(): bool
    {
        return !isset($this->resource) || $this->resource->taskworker;
    }

    /**
     * {@inheritdoc}
     */
    public function send(int $clientId, string $data): void
    {
        $this->resource->send($clientId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function tick(int $millisecond, callable $callback): int
    {
        /* @phpstan-ignore-next-line */
        return $this->resource->tick($millisecond, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function after(int $millisecond, callable $callback): int
    {
        return $this->resource->after($millisecond, $callback);
    }

    public function getConnectionInfo(int $clientId): ?ConnectionInfo
    {
        $clientInfo = $this->resource->getClientInfo($clientId);
        if (empty($clientInfo)) {
            return null;
        }

        return new ConnectionInfo(
            remoteIp: (string) ($clientInfo['remote_ip'] ?? ''),
            remotePort: (int) ($clientInfo['remote_port'] ?? 0),
            serverPort: (int) ($clientInfo['server_port'] ?? 0),
            serverFd: (int) ($clientInfo['server_fd'] ?? 0),
            connectTime: (int) ($clientInfo['connect_time'] ?? 0),
            lastTime: (int) ($clientInfo['last_time'] ?? 0)
        );
    }

    public function sendMessage(MessageInterface $message, int $workerId): void
    {
        $data = serialize($message);
        if ($workerId === $this->resource->worker_id) {
            $this->dispatch(Event::PIPE_MESSAGE->value, [$workerId, $data]);
        } else {
            $this->resource->sendMessage($data, $workerId);
        }
    }

    public function sendMessageToAll(MessageInterface $message): void
    {
        $workers = $this->resource->setting['worker_num'] + $this->resource->setting['task_worker_num'];
        foreach (range(0, $workers - 1) as $workerId) {
            $this->sendMessage($message, $workerId);
        }
    }

    public function getResource(): ?Server
    {
        return $this->resource;
    }

    private function createSwooleServer(ServerPort $port): void
    {
        $swooleServerClass = $port->getServerType()->serverClass();
        $this->resource = new $swooleServerClass($port->getHost(), $port->getPort(), SWOOLE_PROCESS, $port->getSockType());
        $this->resource->set($port->getSettings());

        foreach (Event::cases() as $event) {
            if (!$event->isSwooleEvent() || in_array($event, Event::requestEvents(), true)) {
                continue;
            }
            $this->attach($event);
        }

        foreach ($port->getServerType()->handledEvents() as $event) {
            $this->attach($event);
        }
    }

    private function addPort(ServerPort $port): void
    {
        /** @var Server\Port $swoolePort */
        $swoolePort = $this->resource->addListener($port->getHost(), $port->getPort(), $port->getSockType());
        $swoolePort->set($port->getSettings());

        foreach ($port->getServerType()->handledEvents() as $event) {
            $this->logger->debug(static::TAG."attach $event->value to port ".$port->getPort());
            $swoolePort->on($event->value, $this->createEventHandler($event->value));
        }
    }

    private function createEventHandler(string $eventName): callable
    {
        return function () use ($eventName) {
            $this->logger->debug(static::TAG.'receive event '.$eventName);
            $args = func_get_args();
            if (Event::REQUEST->value === $eventName) {
                $this->onRequest(...$args);

                return;
            }
            if ($args[0] instanceof Server) {
                array_shift($args);
            }
            $this->dispatch($eventName, $args);
        };
    }

    private function attach(Event $event): void
    {
        $this->logger->debug(static::TAG."attach $event->value to server");
        $this->resource->on($event->value, $this->createEventHandler($event->value));
    }

    private function onRequest($request, $response): void
    {
        try {
            /** @var RequestEvent $event */
            $event = $this->dispatch(Event::REQUEST->value, [$this->getSwooleRequestBridge()->create($request)]);
            if ($event) {
                $this->getSwooleResponseBridge()->update($event->getResponse() ?? $this->getResponseFactory()
                        ->createResponse(500), $response);
            }
        } catch (Exception $e) {
            $this->logger->error(static::TAG.'handle http request failed: '.$e->getMessage()."\n"
                .$e->getTraceAsString());
            if (isset($event) && null !== $event->getResponse()) {
                $psrResponse = $event->getResponse();
            } else {
                $psrResponse = $this->getResponseFactory()
                    ->createResponse(500);
            }
            $this->getSwooleResponseBridge()->update($psrResponse, $response);
        }
    }

    public function stats(): array
    {
        return $this->resource->stats();
    }
}
