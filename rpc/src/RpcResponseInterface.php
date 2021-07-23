<?php

declare(strict_types=1);

namespace kuiper\rpc;

use Psr\Http\Message\ResponseInterface;

interface RpcResponseInterface extends ResponseInterface
{
    public function getRequest(): RpcRequestInterface;
}
