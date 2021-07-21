<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use Swoole\Coroutine\Client;

class SwooleCoroutineTcpTransporter extends SwooleTcpTransporter
{
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * {@inheritdoc}
     */
    protected function createSwooleClient()
    {
        return new Client(SWOOLE_TCP);
    }

    protected function doRecv(float $timeout)
    {
        return $this->getResource()->recv($timeout ?? $this->getEndpoint()->getConnectTimeout());
    }
}
