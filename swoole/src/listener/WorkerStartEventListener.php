<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\event\WorkerStartEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class WorkerStartEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * WorkerStartEventListener constructor.
     */
    public function __construct(?LoggerInterface $logger)
    {
        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * @param WorkerStartEvent $event
     */
    public function __invoke($event): void
    {
        $this->changeProcessTitle($event);
        $this->seedRandom();
    }

    public function getSubscribedEvent(): string
    {
        return WorkerStartEvent::class;
    }

    /**
     * @param WorkerStartEvent $event
     */
    private function changeProcessTitle($event): void
    {
        $serverName = $event->getServer()->getServerConfig()->getServerName();
        $title = sprintf('%s: %s%s %d process', $serverName,
            ($event->getServer()->isTaskWorker() ? 'task ' : ''), ProcessType::WORKER, $event->getWorkerId());
        @cli_set_process_title($title);
        $this->logger->debug(static::TAG."start worker {$title}");
    }

    /**
     * @see https://wiki.swoole.com/#/getting_started/notice?id=mt_rand%e9%9a%8f%e6%9c%ba%e6%95%b0
     */
    private function seedRandom(): void
    {
        mt_srand();
    }
}
