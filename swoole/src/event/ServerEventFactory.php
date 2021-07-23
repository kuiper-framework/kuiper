<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

use kuiper\swoole\server\ServerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;

class ServerEventFactory
{
    public function create(string $eventName, array $args): ?AbstractServerEvent
    {
        $method = sprintf('create%sEvent', $eventName);
        if (method_exists($this, $method)) {
            $server = array_shift($args);
            /** @phpstan-ignore-next-line */
            $event = $this->$method(...$args);
            if ($server instanceof ServerInterface) {
                /* @var AbstractServerEvent $event */
                $event->setServer($server);
            }

            return $event;
        }

        return null;
    }

    public function createBootstrapEvent(): BootstrapEvent
    {
        return new BootstrapEvent();
    }

    public function createStartEvent(): StartEvent
    {
        return new StartEvent();
    }

    public function createBeforeReloadEvent(): BeforeReloadEvent
    {
        return new BeforeReloadEvent();
    }

    public function createAfterReloadEvent(): AfterReloadEvent
    {
        return new AfterReloadEvent();
    }

    public function createShutdownEvent(): ShutdownEvent
    {
        return new ShutdownEvent();
    }

    public function createManagerStartEvent(): ManagerStartEvent
    {
        return new ManagerStartEvent();
    }

    public function createManagerStopEvent(): ManagerStopEvent
    {
        return new ManagerStopEvent();
    }

    public function createWorkerStartEvent(int $workerId): WorkerStartEvent
    {
        $event = new WorkerStartEvent();
        $event->setWorkerId($workerId);

        return $event;
    }

    public function createWorkerStopEvent(int $workerId): WorkerStopEvent
    {
        $event = new WorkerStopEvent();
        $event->setWorkerId($workerId);

        return $event;
    }

    public function createWorkerExitEvent(int $workerId): WorkerExitEvent
    {
        $event = new WorkerExitEvent();
        $event->setWorkerId($workerId);

        return $event;
    }

    public function createWorkerErrorEvent(int $workerId, int $workerPid, int $exitCode): WorkerErrorEvent
    {
        $event = new WorkerErrorEvent();
        $event->setWorkerId($workerId);
        $event->setWorkerPid($workerPid);
        $event->setExitCode($exitCode);

        return $event;
    }

    public function createConnectEvent(int $fd, int $reactorId): ConnectEvent
    {
        $event = new ConnectEvent();
        $event->setClientId($fd);
        $event->setReactorId($reactorId);

        return $event;
    }

    public function createCloseEvent(int $fd, int $reactorId): CloseEvent
    {
        $event = new CloseEvent();
        $event->setClientId($fd);
        $event->setReactorId($reactorId);

        return $event;
    }

    public function createRequestEvent(ServerRequestInterface $request): RequestEvent
    {
        $event = new RequestEvent();
        $event->setRequest($request);

        return $event;
    }

    public function createReceiveEvent(int $fd, int $reactorId, string $data): ReceiveEvent
    {
        $event = new ReceiveEvent();
        $event->setClientId($fd);
        $event->setReactorId($reactorId);
        $event->setData($data);

        return $event;
    }

    public function createPacketEvent(string $data, array $clientInfo): PacketEvent
    {
        $event = new PacketEvent();
        $event->setData($data);
        $event->setClientInfo($clientInfo);

        return $event;
    }

    /**
     * @param mixed $data
     */
    public function createTaskEvent(int $taskId, int $fromWorkerId, $data): TaskEvent
    {
        $event = new TaskEvent();
        $event->setTaskId($taskId);
        $event->setFromWorkerId($fromWorkerId);
        $event->setData($data);

        return $event;
    }

    public function createFinishEvent(int $taskId, ?string $result): FinishEvent
    {
        $event = new FinishEvent();
        $event->setTaskId($taskId);
        $event->setResult($result);

        return $event;
    }

    public function createPipeMessageEvent(int $fromWorkerId, string $message): PipeMessageEvent
    {
        $event = new PipeMessageEvent();
        $event->setFromWorkerId($fromWorkerId);
        $event->setMessage($message);

        return $event;
    }

    /**
     * @param Request $request
     */
    public function createOpenEvent($request): OpenEvent
    {
        $event = new OpenEvent();
        $event->setRequest($request);

        return $event;
    }

    /**
     * @param Request  $request
     * @param Response $response
     */
    public function createHandShakeEvent($request, $response): HandShakeEvent
    {
        $event = new HandShakeEvent();
        $event->setRequest($request);
        $event->setResponse($response);

        return $event;
    }

    public function createMessageEvent(Frame $frame): MessageEvent
    {
        $event = new MessageEvent();
        $event->setFrame($frame);

        return $event;
    }
}
