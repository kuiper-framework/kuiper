<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\helper\Enum;

/**
 * Class Algorithm.
 *
 * @property string $implementation
 */
class LoadBalanceAlgorithm extends Enum
{
    public const ROUND_ROBIN = 'round_robin';

    /**
     * @var array
     */
    protected static $PROPERTIES = [
        'implementation' => [
            self::ROUND_ROBIN => RoundRobin::class,
        ],
    ];
}
