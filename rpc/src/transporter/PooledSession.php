<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\swoole\pool\PoolInterface;
use Psr\Http\Message\ResponseInterface;

class PooledSession implements Session
{
    public function __construct(
        private readonly PoolInterface $pool,
        private readonly TransporterInterface $transporter,
        private readonly Session $session)
    {
    }

    public function close(): void
    {
        $this->session->close();
        $this->pool->release($this->transporter);
    }

    public function recv(): ResponseInterface
    {
        return $this->session->recv();
    }
}
