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

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\event\ManagerStartEvent;
use Webmozart\Assert\Assert;

class ManagerStartEventListener implements EventListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(object $event): void
    {
        Assert::isInstanceOf($event, ManagerStartEvent::class);
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, static function (): void {});
        }
        /* @var ManagerStartEvent $event */
        @cli_set_process_title(sprintf('%s: %s process',
            $event->getServer()->getServerConfig()->getServerName(), ProcessType::MANAGER));
    }

    public function getSubscribedEvent(): string
    {
        return ManagerStartEvent::class;
    }
}
