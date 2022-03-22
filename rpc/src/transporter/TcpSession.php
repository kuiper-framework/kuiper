<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use Psr\Http\Message\ResponseInterface;

class TcpSession implements Session
{
    /**
     * @var AbstractTcpTransporter
     */
    private $transporter;

    /**
     * TcpSession constructor.
     *
     * @param AbstractTcpTransporter $transporter
     */
    public function __construct(AbstractTcpTransporter $transporter)
    {
        $this->transporter = $transporter;
    }

    public function close(): void
    {
    }

    public function recv(): ResponseInterface
    {
        return $this->transporter->recv();
    }
}
