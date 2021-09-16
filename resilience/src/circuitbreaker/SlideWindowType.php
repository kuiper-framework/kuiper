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

use kuiper\helper\Enum;

/**
 * @method static SlideWindowType TIME_BASED()  : static
 * @method static SlideWindowType COUNT_BASED() : static
 *
 * @property string $name
 * @property int    $value
 */
class SlideWindowType extends Enum
{
    public const TIME_BASED = 0;
    public const COUNT_BASED = 1;
}
