<?php

declare(strict_types=1);

namespace kuiper\metrics\export;

use kuiper\event\attribute\EventListener;
use kuiper\event\EventListenerInterface;
use kuiper\metrics\Metrics;
use kuiper\swoole\event\WorkerStartEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

#[EventListener]
class StatsdServerExporter implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly StatsdExporter $exporter)
    {
    }

    public function __invoke(object $event): void
    {
        $this->logger->info('export statsd server metrics');
        $swooleServer = $event->getServer()->getResource();
        /** @var WorkerStartEvent $event */
        if (0 === $event->getWorkerId()) {
            $event->getServer()->tick(10000, function () use ($swooleServer) {
                $stats = $swooleServer->stats();
                Metrics::gauge('swoole.accept_count')->set($stats['accept_count']);
                Metrics::gauge('swoole.abort_count')->set($stats['abort_count']);
                Metrics::gauge('swoole.total_recv_bytes')->set($stats['total_recv_bytes']);
                Metrics::gauge('swoole.total_send_bytes')->set($stats['total_send_bytes']);
                Metrics::gauge('swoole.coroutine_num')->set($stats['coroutine_num']);
                $this->exporter->export();
            });
        }
    }

    /**
     * @return string
     */
    public function getSubscribedEvent(): string
    {
        return WorkerStartEvent::class;
    }
}
