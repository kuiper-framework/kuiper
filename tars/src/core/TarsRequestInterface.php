<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\RpcRequestInterface;

interface TarsRequestInterface extends RpcRequestInterface, HasRequestIdInterface
{
    /**
     * @return int
     */
    public function getVersion(): int;

    /**
     * @return int
     */
    public function getPacketType(): int;

    /**
     * @return int
     */
    public function getMessageType(): int;

    /**
     * @return string
     */
    public function getServantName(): string;

    /**
     * @return string
     */
    public function getFuncName(): string;

    /**
     * @return int
     */
    public function getTimeout(): int;

    /**
     * @return string[]
     */
    public function getContext(): array;

    /**
     * @return string[]
     */
    public function getStatus(): array;
}
