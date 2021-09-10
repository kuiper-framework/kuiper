<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerAwareTrait;
use kuiper\event\EventListenerInterface;
use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\event\WorkerStartEvent;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\server\SwooleServer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swoole\Process;
use Webmozart\Assert\Assert;

class WorkerStartEventListener implements EventListenerInterface, LoggerAwareInterface, ContainerAwareInterface
{
    use LoggerAwareTrait;
    use ContainerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';
    /**
     * @var ServerInterface
     */
    private $server;

    /**
     * WorkerStartEventListener constructor.
     */
    public function __construct(?LoggerInterface $logger, ServerInterface $server)
    {
        $this->setLogger($logger ?? new NullLogger());
        $this->server = $server;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        Assert::isInstanceOf($event, WorkerStartEvent::class);
        /* @var WorkerStartEvent $event */
        $this->handleSignal($event);
        $this->changeProcessTitle($event);
        $this->seedRandom();
    }

    /**
     * @param WorkerStartEvent $event
     */
    private function handleSignal(WorkerStartEvent $event): void
    {
        if (function_exists('pcntl_signal') && $event->getServer() instanceof SwooleServer) {
            if ($event->getServer()->isTaskWorker()) {
                pcntl_signal(SIGINT, function (): void {
                    $this->server->stop();
                });
            } else {
                Process::signal(SIGINT, function (): void {
                    $this->server->stop();
                });
            }
        }
    }

    private function changeProcessTitle(WorkerStartEvent $event): void
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

    public function getSubscribedEvent(): string
    {
        return WorkerStartEvent::class;
    }
}
