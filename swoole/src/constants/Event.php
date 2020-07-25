<?php

declare(strict_types=1);

namespace kuiper\swoole\constants;

use kuiper\helper\Enum;

/**
 * Class Event.
 *
 * @property bool non_swoole
 */
class Event extends Enum
{
    public const BOOTSTRAP = 'bootstrap';

    public const START = 'start';

    public const WORKER_START = 'workerStart';

    public const WORKER_STOP = 'workerStop';

    public const WORKER_EXIT = 'workerExit';

    public const WORKER_ERROR = 'workerError';

    public const PIPE_MESSAGE = 'pipeMessage';

    public const REQUEST = 'request';

    public const RECEIVE = 'receive';

    public const CONNECT = 'connect';

    public const HAND_SHAKE = 'handshake';

    public const OPEN = 'open';

    public const MESSAGE = 'message';

    public const CLOSE = 'close';

    public const TASK = 'task';

    public const FINISH = 'finish';

    public const SHUTDOWN = 'shutdown';

    public const PACKET = 'packet';

    public const MANAGER_START = 'managerStart';

    public const MANAGER_STOP = 'managerStop';

    public const BEFORE_RELOAD = 'beforeReload';

    public const AFTER_RELOAD = 'afterReload';

    protected static $PROPERTIES = [
        'non_swoole' => [
            self::BOOTSTRAP => true,
            self::BEFORE_RELOAD => true,
            self::AFTER_RELOAD => true,
        ],
    ];

    public static function requestEvents(): array
    {
        return [self::REQUEST, self::MESSAGE, self::RECEIVE];
    }
}
