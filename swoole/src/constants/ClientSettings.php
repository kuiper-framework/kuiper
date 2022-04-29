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

enum ClientSettings: string
{
    case OPEN_EOF_CHECK = 'open_eof_check';
    case OPEN_LENGTH_CHECK = 'open_length_check';
    case OPEN_MQTT_PROTOCOL = 'open_mqtt_protocol';
    case PACKAGE_LENGTH_TYPE = 'package_length_type';
    case PACKAGE_LENGTH_OFFSET = 'package_length_offset';
    case PACKAGE_BODY_OFFSET = 'package_body_offset';
    case CONNECT_TIMEOUT = 'connect_timeout';
    case RECV_TIMEOUT = 'recv_timeout';
    case PACKAGE_MAX_LENGTH = 'package_max_length';
    case PACKAGE_EOF = 'package_eof';

    public function type(): string
    {
        return match ($this) {
            self::OPEN_EOF_CHECK, self::OPEN_LENGTH_CHECK, self::OPEN_MQTT_PROTOCOL => 'bool',

            self::PACKAGE_LENGTH_TYPE, self::PACKAGE_EOF => 'string',

            self::PACKAGE_LENGTH_OFFSET, self::PACKAGE_BODY_OFFSET, self::CONNECT_TIMEOUT,
            self::PACKAGE_MAX_LENGTH, self::RECV_TIMEOUT => 'int',
        };
    }
}
