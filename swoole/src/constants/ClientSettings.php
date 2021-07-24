<?php

declare(strict_types=1);

namespace kuiper\swoole\constants;

use kuiper\helper\Enum;

class ClientSettings extends Enum
{
    public const OPEN_EOF_CHECK = 'open_eof_check';
    public const OPEN_LENGTH_CHECK = 'open_length_check';
    public const OPEN_MQTT_PROTOCOL = 'open_mqtt_protocol';
    public const PACKAGE_LENGTH_TYPE = 'package_length_type';
    public const PACKAGE_LENGTH_OFFSET = 'package_length_offset';
    public const PACKAGE_BODY_OFFSET = 'package_body_offset';
    public const CONNECT_TIMEOUT = 'connect_timeout';
    public const RECV_TIMEOUT = 'recv_timeout';
    public const PACKAGE_MAX_LENGTH = 'package_max_length';
    public const PACKAGE_EOF = 'package_eof';

    /**
     * @var array
     */
    protected static $PROPERTIES = [
        'type' => [
            self::OPEN_EOF_CHECK => 'bool',
            self::OPEN_LENGTH_CHECK => 'bool',
            self::OPEN_MQTT_PROTOCOL => 'bool',
            self::PACKAGE_LENGTH_TYPE => 'string',
            self::PACKAGE_LENGTH_OFFSET => 'int',
            self::PACKAGE_BODY_OFFSET => 'int',
            self::CONNECT_TIMEOUT => 'int',
            self::RECV_TIMEOUT => 'int',
            self::PACKAGE_MAX_LENGTH => 'int',
            self::PACKAGE_EOF => 'string',
        ],
    ];
}
