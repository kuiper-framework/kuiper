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
use kuiper\tars\integration\StatFServant;
use kuiper\tars\server\stat\StatInterface;
use kuiper\tars\type\StructMap;

class RequestStat implements EventListenerInterface
{
    public function __construct(
        private readonly StatInterface $stat,
        private readonly StatFServant $statFServant)
    {
    }

    public function __invoke(object $event): void
    {
        $config = Application::getInstance()->getConfig();
        if (!$config->getBool('application.tars.client.enable_stat')) {
            return;
        }
        /** @var WorkerStartEvent $event */
        if (!$event->getServer()->isTaskWorker()) {
            return;
        }
        $reportInterval = $config
            ->getInt('application.tars.client.report_interval', 60000);
        $event->getServer()->tick($reportInterval, function (): void {
            $entries = $this->stat->flush();
            if (count($entries) > 0) {
                $map = new StructMap();
                foreach ($entries as $entry) {
                    $map->put($entry->getHead(), $entry->getBody());
                }
                $this->statFServant->reportMicMsg($map, true);
            }
        });
    }

    public function getSubscribedEvent(): string
    {
        return WorkerStartEvent::class;
    }
}
