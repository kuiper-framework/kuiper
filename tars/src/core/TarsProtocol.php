<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\helper\Enum;
use kuiper\swoole\constants\ServerType;

/**
 * @method static TarsProtocol HTTP()      : static
 * @method static TarsProtocol HTTP2()     : static
 * @method static TarsProtocol WEBSOCKET() : static
 * @method static TarsProtocol GRPC()      : static
 * @method static TarsProtocol JSONRPC()   : static
 * @method static TarsProtocol TARS()      : static
 *
 * @property string $serverType
 * @property string $name
 * @property string $value
 */
class TarsProtocol extends Enum
{
    public const HTTP = 'http';
    public const HTTP2 = 'http2';
    public const WEBSOCKET = 'websocket';
    public const GRPC = 'grpc';
    public const JSONRPC = 'jsonrpc';
    public const TARS = 'tars';
    public const NOT_TARS = 'not_tars';

    /**
     * @var array
     */
    protected static $PROPERTIES = [
        'serverType' => [
            self::HTTP => ServerType::HTTP,
            self::HTTP2 => ServerType::HTTP2,
            self::WEBSOCKET => ServerType::WEBSOCKET,
            self::GRPC => ServerType::HTTP2,
            self::TARS => ServerType::TCP,
            self::NOT_TARS => ServerType::HTTP,
        ],
    ];

    public function isHttpProtocol(): bool
    {
        return null !== $this->serverType
            && ServerType::fromValue($this->serverType)->isHttpProtocol();
    }
}
