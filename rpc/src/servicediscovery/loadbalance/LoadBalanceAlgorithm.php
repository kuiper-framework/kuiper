<?php

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
