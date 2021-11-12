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

use Psr\EventDispatcher\EventDispatcherInterface;

interface AsyncEventDispatcherInterface extends EventDispatcherInterface
{
    /**
     * @param object $event
     */
    public function dispatchAsync(object $event): void;
}
