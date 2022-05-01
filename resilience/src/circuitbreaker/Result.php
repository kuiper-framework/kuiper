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

enum Result
{
    case BELOW_THRESHOLDS;
    case FAILURE_RATE_ABOVE_THRESHOLDS;
    case SLOW_CALL_RATE_ABOVE_THRESHOLDS;
    case ABOVE_THRESHOLDS;
    case BELOW_MINIMUM_CALLS_THRESHOLD;

    public static function hasExceededThresholds(Result $result): bool
    {
        return in_array($result, [self::ABOVE_THRESHOLDS, self::FAILURE_RATE_ABOVE_THRESHOLDS, self::SLOW_CALL_RATE_ABOVE_THRESHOLDS], true);
    }

    public static function hasFailureRateExceededThreshold(Result $result): bool
    {
        return in_array($result, [self::ABOVE_THRESHOLDS, self::FAILURE_RATE_ABOVE_THRESHOLDS], true);
    }

    public static function hasSlowCallRateExceededThreshold(Result $result): bool
    {
        return in_array($result, [self::ABOVE_THRESHOLDS, self::SLOW_CALL_RATE_ABOVE_THRESHOLDS], true);
    }
}
