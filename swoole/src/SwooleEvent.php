<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\helper\Enum;

class SwooleEvent extends Enum
{
    /**
     * Swoole onStart event.
     */
    public const START = 'start';

    /**
     * Swoole onWorkerStart event.
     */
    public const WORKER_START = 'workerStart';

    /**
     * Swoole onWorkerStop event.
     */
    public const WORKER_STOP = 'workerStop';

    /**
     * Swoole onWorkerExit event.
     */
    public const WORKER_EXIT = 'workerExit';

    /**
     * Swoole onWorkerErro event.
     */
    public const WORKER_ERROR = 'workerError';

    /**
     * Swoole onPipeMessage event.
     */
    public const PIPE_MESSAGE = 'pipeMessage';

    /**
     * Swoole onRequest event.
     */
    public const REQUEST = 'request';

    /**
     * Swoole onReceive event.
     */
    public const RECEIVE = 'receive';

    /**
     * Swoole onConnect event.
     */
    public const CONNECT = 'connect';

    /**
     * Swoole onHandShake event.
     */
    public const HAND_SHAKE = 'handshake';

    /**
     * Swoole onOpen event.
     */
    public const OPEN = 'open';

    /**
     * Swoole onMessage event.
     */
    public const MESSAGE = 'message';

    /**
     * Swoole onClose event.
     */
    public const CLOSE = 'close';

    /**
     * Swoole onTask event.
     */
    public const TASK = 'task';

    /**
     * Swoole onFinish event.
     */
    public const FINISH = 'finish';

    /**
     * Swoole onShutdown event.
     */
    public const SHUTDOWN = 'shutdown';

    /**
     * Swoole onPacket event.
     */
    public const PACKET = 'packet';

    /**
     * Swoole onManagerStart event.
     */
    public const MANAGER_START = 'managerStart';

    /**
     * Swoole onManagerStop event.
     */
    public const MANAGER_STOP = 'managerStop';

    public static function requestEvents(): array
    {
        return [self::REQUEST, self::MESSAGE, self::RECEIVE];
    }
}
