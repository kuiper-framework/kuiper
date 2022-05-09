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

namespace kuiper\tars\server\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\Application;
use kuiper\swoole\event\WorkerStartEvent;
use kuiper\tars\server\monitor\MonitorInterface;
use kuiper\web\middleware\RemoteAddress;

class ServiceMonitor implements EventListenerInterface
{
    public function __construct(private readonly MonitorInterface $monitor)
    {
    }

    public function __invoke(object $event): void
    {
        $config = Application::getInstance()->getConfig();
        if (!$config->getBool('application.tars.server.enable_monitor')) {
            return;
        }
        /** @var WorkerStartEvent $event */
        if (!$event->getServer()->isTaskWorker()) {
            return;
        }
        $reportInterval = $config->getInt('application.tars.client.report_interval', 60000);
        $event->getServer()->tick($reportInterval, function (): void {
            $this->monitor->report();
        });
    }

    public function getSubscribedEvent(): string
    {
        return WorkerStartEvent::class;
    }
}
