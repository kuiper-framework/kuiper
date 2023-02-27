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

namespace kuiper\tars\exception;

use Exception;

class TarsStreamException extends Exception
{
    public const TYPE_NOT_MATCH = 28001;
    public const LENGTH_NOT_MATCH = 28002;
    public const VAR_NOT_FOUND = 28003;
    public const OUT_OF_RANGE = 28004;
    public const STRING_TYPE_UNKNOWN = 28005;
    public const HEADER_TYPE_UNKNOWN = 28006;
    public const HEADER_TYPE_ERROR = 28007;
    public const HEADER_TAG_ERROR = 28008;
    public const OBJECT_NOT_FOUND = 28009;
    public const STREAM_LEN_ERROR = 28010;
    public const TAG_NOT_MATCH = 28011;
    public const UNKNOWN_SERVICE = 28012;
    public const UNKNOWN_FUNCTION = 28013;
    public const PROTO_TYPE_UNKNOWN = 28014;

    public static function typeNotMatch(string $message): self
    {
        return new self($message, self::TYPE_NOT_MATCH);
    }

    public static function streamLenError(): self
    {
        return new self('expect not end', self::STREAM_LEN_ERROR);
    }

    public static function tagNotMatch(): self
    {
        return new self('tag not match', self::TAG_NOT_MATCH);
    }

    public static function outOfRange(): self
    {
        return new self('data error', self::OUT_OF_RANGE);
    }
}
