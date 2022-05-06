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

namespace kuiper\jsonrpc\exception;

class ErrorCode
{
    public const ERROR_PARSE = -32700;
    public const ERROR_INVALID_REQUEST = -32600;
    public const ERROR_INVALID_METHOD = -32601;
    public const ERROR_INVALID_PARAMS = -32602;
    public const ERROR_INTERNAL = -32603;
    public const ERROR_OTHER = -32000;
}
