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
    /**
     * @var StatInterface
     */
    private $stat;

    /**
     * @var StatFServant
     */
    private $statFServant;

    /**
     * RequestStat constructor.
     *
     * @param StatInterface $stat
     * @param StatFServant  $statFServant
     */
    public function __construct(StatInterface $stat, StatFServant $statFServant)
    {
        $this->stat = $stat;
        $this->statFServant = $statFServant;
    }

    public function __invoke($event): void
    {
        $config = Application::getInstance()->getConfig();
        /** @var WorkerStartEvent $event */
        if (!$event->getServer()->isTaskWorker()
        || '' === $config->getString('application.tars.server.node')) {
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
