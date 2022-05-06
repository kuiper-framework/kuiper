<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use Psr\Http\Message\ResponseInterface;

class SimpleSession implements Session
{
    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function close(): void
    {
    }

    public function recv(): ResponseInterface
    {
        return $this->response;
    }
}
