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

use Swoole\Http\Server as HttpServer;
use Swoole\Server;

enum ServerType: string
{
    case HTTP = 'http';
    case HTTP2 = 'http2';
    case WEBSOCKET = 'websocket';
    case TCP = 'tcp';
    case UDP = 'udp';

    public function isHttpProtocol(): bool
    {
        return in_array(Event::REQUEST, $this->handledEvents(), true);
    }

    public function serverClass(): string
    {
        return match ($this) {
            self::HTTP, self::HTTP2, self::WEBSOCKET => HttpServer::class,
            self::TCP, self::UDP => Server::class,
        };
    }

    public function settings(): array
    {
        return match ($this) {
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
            self::TCP, self::UDP => [
                ServerSetting::OPEN_HTTP_PROTOCOL => false,
                ServerSetting::OPEN_HTTP2_PROTOCOL => false,
            ],
        };
    }

    /**
     * @return Event[]
     */
    public function handledEvents(): array
    {
        return match ($this) {
            self::HTTP, self::HTTP2 => [Event::REQUEST],
            self::WEBSOCKET => [Event::REQUEST, Event::MESSAGE, Event::OPEN],
            self::TCP => [Event::RECEIVE],
            self::UDP => [Event::PACKET],
        };
    }
}
