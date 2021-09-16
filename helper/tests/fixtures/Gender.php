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
