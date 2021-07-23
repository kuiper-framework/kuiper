<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\RpcRequestInterface;

interface TarsRequestInterface extends RpcRequestInterface, HasRequestIdInterface
{
    public function getVersion(): int;

    public function getPacketType(): int;

    public function getMessageType(): int;

    public function getRequestId(): int;

    public function getServantName(): string;

    public function getFuncName(): string;

    public function getTimeout(): int;

    public function getContext(): array;

    public function getStatus(): array;
}
