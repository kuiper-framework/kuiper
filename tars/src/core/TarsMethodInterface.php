<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\rpc\RpcMethodInterface;

interface TarsMethodInterface extends RpcMethodInterface
{
    /**
     * @return ParameterInterface[]
     */
    public function getParameters(): array;

    /**
     * @return ParameterInterface
     */
    public function getReturnValue(): ParameterInterface;
}
