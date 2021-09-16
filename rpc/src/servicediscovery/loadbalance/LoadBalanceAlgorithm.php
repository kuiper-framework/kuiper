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

namespace kuiper\rpc\servicediscovery\loadbalance;

use kuiper\helper\Enum;

/**
 * Class Algorithm.
 *
 * @property string $implementation
 */
class LoadBalanceAlgorithm extends Enum
{
    public const ROUND_ROBIN = 'round_robin';
    public const RANDOM = 'random';
    public const EQUALITY = 'equality';

    /**
     * @var array
     */
    protected static $PROPERTIES = [
        'implementation' => [
            self::ROUND_ROBIN => RoundRobin::class,
            self::RANDOM => Random::class,
            self::EQUALITY => Equality::class,
        ],
    ];
}
