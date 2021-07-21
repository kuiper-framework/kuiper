<?php

declare(strict_types=1);

namespace kuiper\tars\stream;

use kuiper\helper\Enum;

final class TarsType extends Enum
{
    public const INT8 = 0;
    public const INT16 = 1;
    public const INT32 = 2;
    public const INT64 = 3;
    public const FLOAT = 4;
    public const DOUBLE = 5;
    public const STRING1 = 6;
    public const STRING4 = 7;
    public const MAP = 8;
    public const VECTOR = 9;
    public const STRUCT_BEGIN = 10;
    public const STRUCT_END = 11;
    public const ZERO = 12;
    public const SIMPLE_LIST = 13;
}
