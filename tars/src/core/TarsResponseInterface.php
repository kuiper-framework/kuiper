<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\stream\ResponsePacket;

interface TarsResponseInterface extends RpcResponseInterface
{
    /**
     * @return ResponsePacket
     */
    public function getResponsePacket(): ResponsePacket;
}
