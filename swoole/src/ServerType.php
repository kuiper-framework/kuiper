<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\helper\Enum;
use Swoole\Http\Server;

/**
 * Class SwooleServerType.
 *
 * @property string $server
 * @property array  $settings
 * @property array  $events
 */
class ServerType extends Enum
{
    public const HTTP = 'http';
    public const HTTP2 = 'http2';
    public const WEBSOCKET = 'websocket';
    public const TCP = 'tcp';
    public const UDP = 'udp';

    protected static $PROPERTIES = [
        'server' => [
            self::HTTP => Server::class,
            self::HTTP2 => Server::class,
            self::WEBSOCKET => Server::class,
            self::TCP => \Swoole\Server::class,
            self::UDP => \Swoole\Server::class,
        ],
        'settings' => [
            self::HTTP => [
                SwooleSetting::OPEN_HTTP_PROTOCOL => true,
                SwooleSetting::OPEN_HTTP2_PROTOCOL => false,
            ],
            self::HTTP2 => [
                SwooleSetting::OPEN_HTTP_PROTOCOL => false,
                SwooleSetting::OPEN_HTTP2_PROTOCOL => true,
            ],
            self::WEBSOCKET => [
                SwooleSetting::OPEN_WEBSOCKET_PROTOCOL => true,
                SwooleSetting::OPEN_HTTP2_PROTOCOL => false,
            ],
            self::TCP => [
                SwooleSetting::OPEN_HTTP_PROTOCOL => false,
                SwooleSetting::OPEN_HTTP2_PROTOCOL => false,
            ],
            self::UDP => [
                SwooleSetting::OPEN_HTTP_PROTOCOL => false,
                SwooleSetting::OPEN_HTTP2_PROTOCOL => false,
            ],
        ],
        'events' => [
            self::HTTP => [SwooleEvent::REQUEST],
            self::HTTP2 => [SwooleEvent::REQUEST],
            self::WEBSOCKET => [SwooleEvent::REQUEST, SwooleEvent::MESSAGE, SwooleEvent::OPEN, SwooleEvent::HAND_SHAKE],
            self::TCP => [SwooleEvent::RECEIVE],
            self::UDP => [SwooleEvent::PACKET],
        ],
    ];
}
