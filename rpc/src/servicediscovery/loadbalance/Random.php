<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery\loadbalance;

class Random implements LoadBalanceInterface
{
    /**
     * @var array
     */
    private $hosts;

    public function __construct(array $hosts)
    {
        $this->hosts = $hosts;
    }

    public function select()
    {
        return $this->hosts[array_rand($this->hosts)];
    }
}
