<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

use kuiper\helper\Enum;

/**
 * @method static Outcome SUCCESS()      : static
 * @method static Outcome ERROR()        : static
 * @method static Outcome SLOW_SUCCESS() : static
 * @method static Outcome SLOW_ERROR()   : static
 *
 * @property string $name
 * @property int    $value
 */
class Outcome extends Enum
{
    public const SUCCESS = 0;
    public const ERROR = 1;
    public const SLOW_SUCCESS = 2;
    public const SLOW_ERROR = 3;
}
