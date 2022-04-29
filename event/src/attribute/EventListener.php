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

namespace kuiper\event\attribute;

use Attribute;
use kuiper\di\attribute\ComponentTrait;
use kuiper\di\Component;

#[Attribute(Attribute::TARGET_CLASS)]
final class EventListener implements Component
{
    use ComponentTrait;

    public function __construct(private ?string $eventName = null)
    {
    }

    public function getEventName(): ?string
    {
        return $this->eventName;
    }
}
