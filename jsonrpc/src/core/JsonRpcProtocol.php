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

namespace kuiper\jsonrpc\core;

class JsonRpcProtocol
{
    public const EOF = "\r\n";

    public const NS = 'jsonrpc';

    public static function encode(array $data): string
    {
        return json_encode($data).self::EOF;
    }
}
