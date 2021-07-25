<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

interface LoadBalanceInterface
{
    /**
     * @return mixed
     */
    public function select();
}
