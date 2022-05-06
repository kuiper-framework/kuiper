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

class RoundRobin implements LoadBalanceInterface
{
    /**
     * @var array
     */
    private readonly array $hosts;

    /**
     * @var array
     */
    private $states;

    public function __construct(array $hosts, private readonly array $weights)
    {
        if (empty($hosts)) {
            throw new \InvalidArgumentException('hosts should not be empty');
        }
        $this->hosts = $hosts;
        $this->states = [];
        foreach ($hosts as $key => $item) {
            $this->states[$key] = ['weight' => 0, 'count' => 0];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function select(): mixed
    {
        $total = 0;
        $best = null;

        foreach ($this->hosts as $key => $item) {
            $weight = $this->weights[$key] ?? 100;
            $this->states[$key]['weight'] += $weight;

            $total += $weight;

            if ((null === $best)
                 || ($this->states[$best]['weight'] < $this->states[$key]['weight'])) {
                $best = $key;
            }
        }
        $this->states[$best]['weight'] -= $total;
        ++$this->states[$best]['count'];

        return $this->hosts[$best];
    }
}
