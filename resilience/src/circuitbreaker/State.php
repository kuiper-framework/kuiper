<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use kuiper\helper\Enum;

/**
 * @method static State CLOSED()      : static
 * @method static State OPEN()        : static
 * @method static State HALF_OPEN()   : static
 * @method static State DISABLED()    : static
 * @method static State FORCED_OPEN() : static
 *
 * @property int[]  $next_state
 * @property string $name
 * @property int    $value
 */
class State extends Enum
{
    public const CLOSED = 0;
    public const OPEN = 1;
    public const HALF_OPEN = 2;
    public const DISABLED = 3;
    public const FORCED_OPEN = 4;

    /**
     * @var array
     */
    protected static $PROPERTIES = [
        'next_state' => [
            self::CLOSED => [self::OPEN],
            self::HALF_OPEN => [self::OPEN, self::CLOSED],
            self::OPEN => [self::HALF_OPEN],
        ],
    ];

    public function canTransfer(State $nextState): bool
    {
        if (!isset($this->next_state)) {
            return true;
        }

        return in_array($nextState->value, $this->next_state, true);
    }
}
