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

enum LoadBalanceAlgorithm: string
{
    case ROUND_ROBIN = 'round_robin';
    case RANDOM = 'random';
    case EQUALITY = 'equality';
}
