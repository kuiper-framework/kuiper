<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use kuiper\helper\Enum;

/**
 * @method static Result BELOW_THRESHOLDS()                : static
 * @method static Result FAILURE_RATE_ABOVE_THRESHOLDS()   : static
 * @method static Result SLOW_CALL_RATE_ABOVE_THRESHOLDS() : static
 * @method static Result ABOVE_THRESHOLDS()                : static
 * @method static Result BELOW_MINIMUM_CALLS_THRESHOLD()   : static
 *
 * @property string $name
 * @property int    $value
 */
class Result extends Enum
{
    public const BELOW_THRESHOLDS = 0;
    public const FAILURE_RATE_ABOVE_THRESHOLDS = 1;
    public const SLOW_CALL_RATE_ABOVE_THRESHOLDS = 2;
    public const ABOVE_THRESHOLDS = 3;
    public const BELOW_MINIMUM_CALLS_THRESHOLD = 4;

    public static function hasExceededThresholds(Result $result): bool
    {
        return in_array($result->value, [self::ABOVE_THRESHOLDS, self::FAILURE_RATE_ABOVE_THRESHOLDS, self::SLOW_CALL_RATE_ABOVE_THRESHOLDS], true);
    }

    public static function hasFailureRateExceededThreshold(Result $result): bool
    {
        return in_array($result->value, [self::ABOVE_THRESHOLDS, self::FAILURE_RATE_ABOVE_THRESHOLDS], true);
    }

    public static function hasSlowCallRateExceededThreshold(Result $result): bool
    {
        return in_array($result->value, [self::ABOVE_THRESHOLDS, self::SLOW_CALL_RATE_ABOVE_THRESHOLDS], true);
    }
}
