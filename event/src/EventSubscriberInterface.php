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

namespace kuiper\event;

interface EventSubscriberInterface
{
    /**
     * @param object $event
     */
    public function __invoke($event): void;

    /**
     * Returns the event name this event listener wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents(): array;
}
