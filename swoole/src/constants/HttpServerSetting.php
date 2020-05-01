<?php

declare(strict_types=1);

namespace kuiper\swoole\constants;

use kuiper\helper\Enum;

class HttpServerSetting extends Enum
{
    public const GZIP = 'gzip';
    public const KEEPALIVE = 'keepalive';
    public const EXPIRE = 'expire';
    public const GZIP_LEVEL = 'gzip_level';
}
