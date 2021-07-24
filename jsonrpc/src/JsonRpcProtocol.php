<?php

declare(strict_types=1);

namespace kuiper\jsonrpc;

class JsonRpcProtocol
{
    public const EOF = "\r\n";

    public static function encode(array $data): string
    {
        return json_encode($data).self::EOF;
    }
}
