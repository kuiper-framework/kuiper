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

namespace kuiper\db\constants;

use PDOException;

class ErrorCode
{
    public const CR_SERVER_GONE_ERROR = 2006;
    public const CR_SERVER_LOST = 2013;

    public static function isRetryable(PDOException $e): bool
    {
        return isset($e->errorInfo[1])
            && in_array($e->errorInfo[1], [self::CR_SERVER_LOST, self::CR_SERVER_GONE_ERROR], true);
    }
}
