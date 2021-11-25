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

namespace kuiper\tars\stream;

class TarsConst
{
    public const MAX_TAG_VALUE = 15;
    public const MIN_INT8 = -128;
    public const MAX_INT8 = 127;
    public const MAX_UINT8 = 255;
    public const MIN_INT16 = -32768;
    public const MAX_INT16 = 32767;
    public const MAX_UINT16 = 65535;
    public const MIN_INT32 = -2147483648;
    public const MAX_INT32 = 2147483647;
    public const MAX_UINT32 = 4294967295;
    public const MAX_STRING1_LEN = 255;
    public const PACKET_TYPE = 0;
    public const MESSAGE_TYPE = 0;
    public const VERSION = 3;
    public const TIMEOUT = 2000;
    public const RESULT_CODE = '__CODE';
    public const RESULT_DESC = '__DESC';

    public static function check(): void
    {
        if (PHP_INT_SIZE !== 8) {
            throw new \RuntimeException('Your php is not 64bits');
        }
    }
}
