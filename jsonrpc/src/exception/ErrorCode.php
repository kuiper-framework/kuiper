<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\exception;

use kuiper\helper\Enum;

class ErrorCode extends Enum
{
    public const ERROR_PARSE = -32700;
    public const ERROR_INVALID_REQUEST = -32600;
    public const ERROR_INVALID_METHOD = -32601;
    public const ERROR_INVALID_PARAMS = -32602;
    public const ERROR_INTERNAL = -32603;
    public const ERROR_OTHER = -32000;
}
