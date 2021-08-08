<?php

declare(strict_types=1);

namespace kuiper\tars\server\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\Application;
use kuiper\swoole\event\StartEvent;
use kuiper\swoole\task\QueueInterface;
use kuiper\tars\server\task\KeepAlive;
use kuiper\tars\server\task\ReportTask;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class StartListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var QueueInterface
     */
    private $taskQueue;

    /**
     * StartListener constructor.
     *
     * @param QueueInterface $taskQueue
     */
    public function __construct(QueueInterface $taskQueue)
    {
        $this->taskQueue = $taskQueue;
    }

    public function __invoke($event): void
    {
        /** @var StartEvent $event */
        $server = $event->getServer();
        $config = Application::getInstance()->getConfig();
        if ('' === $config->getString('application.tars.server.node')) {
            $this->logger->debug(static::TAG.'healthy check is disabled.');
        } else {
            $server->tick($config->getInt('application.tars.server.keep-alive-interval', 10000), function () {
                $this->taskQueue->put(new KeepAlive());
            });
        }

        if ('' === $config->getString('application.tars.client.locator')) {
            $this->logger->debug(static::TAG.'report is disabled.');
        } else {
            $server->tick($config->getInt('application.tars.client.report-interval', 60000), function () {
                $this->taskQueue->put(new ReportTask());
            });
        }
    }

    public function getSubscribedEvent(): string
    {
        return StartEvent::class;
    }
}
