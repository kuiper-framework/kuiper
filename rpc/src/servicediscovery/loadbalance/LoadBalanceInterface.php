<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery\loadbalance;

interface LoadBalanceInterface
{
    /**
     * @return mixed
     */
    public function select();
}
