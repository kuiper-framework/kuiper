<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\swoole\pool\PoolInterface;
use Psr\Http\Message\RequestInterface;
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
     * PooledSession constructor.
     *
     * @param PoolInterface    $pool
     * @param RequestInterface $request
     */
    public function __construct(PoolInterface $pool, Session $session)
    {
        $this->pool = $pool;
        $this->session = $session;
    }

    public function close(): void
    {
        $this->session->close();
        $this->pool->release();
    }

    public function recv(): ResponseInterface
    {
        return $this->session->recv();
    }
}
