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

namespace kuiper\rpc\server\admin;

use kuiper\swoole\server\ServerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class AdminServantImpl implements AdminServant, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ServerInterface $server)
    {
    }

    public function ping(): string
    {
        return 'pong';
    }

    public function stats(): Stat
    {
        // todo: aggregate all workers stats
        $statArr = $this->server->stats();

        return new Stat(
            startTime: date('c', $statArr['start_time'] ?? time()),
            connections: $statArr['connection_num'] ?? 0,
            acceptCount: $statArr['accept_count'] ?? 0,
            closeCount: $statArr['close_count'] ?? 0,
            requestCount: $statArr['request_count'] ?? 0,
            dispatchCount: $statArr['dispatch_count'] ?? 0,
            pendingTasks: $statArr['tasking_num'] ?? 0,
        );
    }

    public function notify(Notification $notification): void
    {
        $this->logger->info(static::TAG.'receive admin notification', ['message' => $notification]);
        $this->eventDispatcher->dispatch($notification);
    }
}
