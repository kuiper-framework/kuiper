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

namespace kuiper\resilience\circuitbreaker;

enum State: int
{
    case CLOSED = 0;
    case OPEN = 1;
    case HALF_OPEN = 2;
    case DISABLED = 3;
    case FORCED_OPEN = 4;

    public function nextTransitions(): array
    {
        return match ($this) {
            self::CLOSED => [self::OPEN],
            self::HALF_OPEN => [self::OPEN, self::CLOSED],
            self::OPEN => [self::HALF_OPEN],
            default => []
        };
    }

    public function canTransitionTo(State $nextState): bool
    {
        $next = $this->nextTransitions();
        if (empty($next)) {
            return true;
        }

        return in_array($nextState, $next, true);
    }
}
