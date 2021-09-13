<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\swoole\server\ServerInterface;
use kuiper\tars\server\servant\AdminServant;
use kuiper\tars\server\servant\Notification;
use kuiper\tars\server\servant\Stat;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class AdminServantImpl implements AdminServant, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ServerInterface
     */
    private $server;

    /**
     * AdminServantImpl constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param ServerInterface          $server
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, ServerInterface $server)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->server = $server;
    }

    public function ping(): string
    {
        return 'pong';
    }

    public function stats(): Stat
    {
        $stat = new Stat();
        // todo: aggregate all workers stats
        $statArr = $this->server->stats();
        $stat->startTime = date('c', $statArr['start_time'] ?? time());
        $stat->acceptCount = $statArr['accept_count'] ?? 0;
        $stat->closeCount = $statArr['close_count'] ?? 0;
        $stat->connections = $statArr['connection_num'] ?? 0;
        $stat->dispatchCount = $statArr['dispatch_count'] ?? 0;
        $stat->requestCount = $statArr['request_count'] ?? 0;
        $stat->pendingTasks = $statArr['tasking_num'] ?? 0;

        return $stat;
    }

    public function notify(Notification $notification): void
    {
        $this->logger->info(static::TAG.'receive admin notification', ['message' => $notification]);
        $this->eventDispatcher->dispatch($notification);
    }
}
