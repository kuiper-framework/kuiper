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

final class JsonRpcProtocol
{
    public const EOF = "\r\n";

    public const NS = 'jsonrpc';

    public const EXTENDED_VERSION = '1.0';

    public const VERSION = '2.0';

    public const JSONRPC = 'jsonrpc';

    public const ID = 'id';
    public const METHOD = 'method';
    public const PARAMS = 'params';
    public const RESULT = 'result';

    public const EXTENDED = '@extended';

    public static function encode(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR).self::EOF;
    }
}
