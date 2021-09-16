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

namespace kuiper\swoole\constants;

use kuiper\helper\Enum;

class HttpServerSetting extends Enum
{
    public const GZIP = 'gzip';
    public const KEEPALIVE = 'keepalive';
    public const EXPIRE = 'expire';
    public const GZIP_LEVEL = 'gzip_level';
}
