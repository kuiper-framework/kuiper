<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\swoole\pool\PoolInterface;
use Psr\Http\Message\ResponseInterface;

class PooledSession implements Session
{
    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TransporterInterface
     */
    private $transporter;

    public function __construct(PoolInterface $pool, TransporterInterface $transporter, Session $session)
    {
        $this->pool = $pool;
        $this->session = $session;
        $this->transporter = $transporter;
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
