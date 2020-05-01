<?php

declare(strict_types=1);

namespace kuiper\swoole\constants;

use kuiper\helper\Enum;
use Swoole\Http\Server as HttpServer;
use Swoole\Server;

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
            self::HTTP => HttpServer::class,
            self::HTTP2 => HttpServer::class,
            self::WEBSOCKET => HttpServer::class,
            self::TCP => Server::class,
            self::UDP => Server::class,
        ],
        'settings' => [
            self::HTTP => [
                ServerSetting::OPEN_HTTP_PROTOCOL => true,
                ServerSetting::OPEN_HTTP2_PROTOCOL => false,
            ],
            self::HTTP2 => [
                ServerSetting::OPEN_HTTP_PROTOCOL => false,
                ServerSetting::OPEN_HTTP2_PROTOCOL => true,
            ],
            self::WEBSOCKET => [
                ServerSetting::OPEN_WEBSOCKET_PROTOCOL => true,
                ServerSetting::OPEN_HTTP2_PROTOCOL => false,
            ],
            self::TCP => [
                ServerSetting::OPEN_HTTP_PROTOCOL => false,
                ServerSetting::OPEN_HTTP2_PROTOCOL => false,
            ],
            self::UDP => [
                ServerSetting::OPEN_HTTP_PROTOCOL => false,
                ServerSetting::OPEN_HTTP2_PROTOCOL => false,
            ],
        ],
        'events' => [
            self::HTTP => [Event::REQUEST],
            self::HTTP2 => [Event::REQUEST],
            self::WEBSOCKET => [Event::REQUEST, Event::MESSAGE, Event::OPEN, Event::HAND_SHAKE],
            self::TCP => [Event::RECEIVE],
            self::UDP => [Event::PACKET],
        ],
    ];

    public function isHttpProtocol(): bool
    {
        return in_array(Event::REQUEST, $this->events, true);
    }
}
