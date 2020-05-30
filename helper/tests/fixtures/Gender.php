<?php

declare(strict_types=1);

namespace kuiper\helper\fixtures;

use kuiper\helper\Enum;

class Gender extends Enum
{
    public const MALE = 'm';

    public const FEMALE = 'f';

    protected static $PROPERTIES = [
        'description' => [
            self::MALE => '男',
            self::FEMALE => '女',
        ],
        'enName' => [
            self::MALE => 'male',
        ],
    ];
}
