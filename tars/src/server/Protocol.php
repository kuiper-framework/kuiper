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

namespace kuiper\tars\server;

use kuiper\swoole\constants\ServerType;

enum Protocol: string
{
    case HTTP = 'http';
    case HTTP2 = 'http2';
    case WEBSOCKET = 'websocket';
    case GRPC = 'grpc';
    case JSONRPC = 'jsonrpc';
    case TARS = 'tars';

    public function getServerType(): ?ServerType
    {
        return match($this) {
            self::HTTP => ServerType::HTTP,
            self::HTTP2, self::GRPC => ServerType::HTTP2,
            self::WEBSOCKET => ServerType::WEBSOCKET,
            default => null
        };
    }

    public function isHttpProtocol(): bool
    {
        return $this->getServerType()?->isHttpProtocol() ?? false;
    }
}
