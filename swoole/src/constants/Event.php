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

namespace kuiper\swoole\constants;

enum Event: string
{
    case BOOTSTRAP = 'bootstrap';
    case START = 'start';
    case WORKER_START = 'workerStart';
    case WORKER_STOP = 'workerStop';
    case WORKER_EXIT = 'workerExit';
    case WORKER_ERROR = 'workerError';
    case PIPE_MESSAGE = 'pipeMessage';
    case REQUEST = 'request';
    case RECEIVE = 'receive';
    case CONNECT = 'connect';
    case HAND_SHAKE = 'handshake';
    case OPEN = 'open';
    case MESSAGE = 'message';
    case CLOSE = 'close';
    case TASK = 'task';
    case FINISH = 'finish';
    case SHUTDOWN = 'shutdown';
    case PACKET = 'packet';
    case MANAGER_START = 'managerStart';
    case MANAGER_STOP = 'managerStop';
    case BEFORE_RELOAD = 'beforeReload';
    case AFTER_RELOAD = 'afterReload';
    public static function requestEvents(): array
    {
        return [self::REQUEST, self::MESSAGE, self::RECEIVE];
    }

    public function isSwooleEvent(): bool
    {
        return match ($this) {
            self::BOOTSTRAP, self::BEFORE_RELOAD, self::AFTER_RELOAD => false,
            default => true,
        };
    }
}
