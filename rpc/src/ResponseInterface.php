<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface ResponseInterface extends \Psr\Http\Message\ResponseInterface
{
    public function getRequest(): RequestInterface;
}
