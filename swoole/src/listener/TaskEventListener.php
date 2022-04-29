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
use kuiper\swoole\event\TaskEvent;
use kuiper\swoole\task\DispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Webmozart\Assert\Assert;

class TaskEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * TaskEventListener constructor.
     */
    public function __construct(private readonly DispatcherInterface $taskDispatcher)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(object $event): void
    {
        Assert::isInstanceOf($event, TaskEvent::class);
        /* @var TaskEvent $event */
        $this->taskDispatcher->dispatch($event);
    }

    public function getSubscribedEvent(): string
    {
        return TaskEvent::class;
    }
}
