<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use Psr\Http\Message\ResponseInterface;

class TcpSession implements Session
{
    public function __construct(private readonly AbstractTcpTransporter $transporter)
    {
    }

    public function close(): void
    {
    }

    public function recv(): ResponseInterface
    {
        return $this->transporter->recv();
    }
}
