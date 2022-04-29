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
use kuiper\swoole\event\PipeMessageEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class PipeMessageEventListener implements EventListenerInterface
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function __invoke(object $event): void
    {
        /** @var PipeMessageEvent $event */
        $this->eventDispatcher->dispatch($event->getMessage());
    }

    public function getSubscribedEvent(): string
    {
        return PipeMessageEvent::class;
    }
}
