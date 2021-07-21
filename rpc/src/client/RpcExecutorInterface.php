<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\exception\CommunicationException;

interface RpcExecutorInterface
{
    /**
     * @throws CommunicationException
     */
    public function execute(): array;
}
