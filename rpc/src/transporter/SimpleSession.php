<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use Psr\Http\Message\ResponseInterface;

class SimpleSession implements Session
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * SimpleSession constructor.
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function close(): void
    {
    }

    public function recv(): ResponseInterface
    {
        return $this->response;
    }
}
