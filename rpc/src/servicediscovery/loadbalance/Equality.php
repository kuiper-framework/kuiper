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

use ArrayIterator;

class Equality implements LoadBalanceInterface
{
    /**
     * @var ArrayIterator
     */
    private readonly ArrayIterator $hosts;

    public function __construct(array $hosts)
    {
        $this->hosts = new ArrayIterator($hosts);
    }

    public function select(): mixed
    {
        if (!$this->hosts->valid()) {
            $this->hosts->rewind();
        }
        $value = $this->hosts->current();
        $this->hosts->next();

        return $value;
    }
}
