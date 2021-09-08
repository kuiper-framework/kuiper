<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\swoole\pool\PoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PooledTransporter implements TransporterInterface
{
    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * PooledTransporter constructor.
     */
    public function __construct(PoolInterface $pool)
    {
        $this->pool = $pool;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->pool->take()->send($request);
    }

    public function recv(): ResponseInterface
    {
        return $this->pool->take()->recv();
    }
}
