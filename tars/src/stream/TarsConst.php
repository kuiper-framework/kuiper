<?php

declare(strict_types=1);

namespace kuiper\tars\stream;

class TarsConst
{
    public const MAX_TAG_VALUE = 15;
    public const MIN_INT8 = -128;
    public const MAX_INT8 = 127;
    public const MIN_INT16 = -32768;
    public const MAX_INT16 = 32767;
    public const MIN_INT32 = -2147483648;
    public const MAX_INT32 = 2147483647;
    public const MAX_STRING1_LEN = 255;

    public static function check(): void
    {
        if (PHP_INT_SIZE !== 8) {
            throw new \RuntimeException('Your php is not 64bits');
        }
    }
}
