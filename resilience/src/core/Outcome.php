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
