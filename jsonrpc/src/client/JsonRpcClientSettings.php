<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

enum JsonRpcClientSettings
{
    case TIMEOUT;

    public function type(): string
    {
        return match ($this) {
            self::TIMEOUT => 'float',
        };
    }
}
