<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface RpcServerRequestInterface extends RpcRequestInterface
{
    /**
     * Retrieve server parameters.
     *
     * @return array
     */
    public function getServerParams();
}
