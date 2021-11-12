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

namespace kuiper\rpc\client;

use kuiper\resilience\core\Counter;

class RequestIdGenerator implements RequestIdGeneratorInterface
{
    /**
     * @var Counter
     */
    private $counter;

    /**
     * RequestIdGenerator constructor.
     *
     * @param Counter $counter
     */
    public function __construct(Counter $counter, int $start = null)
    {
        $this->counter = $counter;
        if (!isset($start)) {
            $start = random_int(0, 1 << 20);
        }
        $this->counter->set($start);
    }

    /**
     * {@inheritDoc}
     */
    public function next(): int
    {
        return $this->counter->increment();
    }
}
