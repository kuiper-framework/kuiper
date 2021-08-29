<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\core;

class JsonRpcProtocol
{
    public const EOF = "\r\n";

    public static function encode(array $data): string
    {
        return json_encode($data).self::EOF;
    }
}
