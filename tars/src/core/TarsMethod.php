<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\rpc\RpcMethod;

class TarsMethod extends RpcMethod implements TarsMethodInterface
{
    /**
     * @var ParameterInterface[]
     */
    private $parameters;

    /**
     * @var ParameterInterface
     */
    private $returnValue;

    /**
     * TarsMethod constructor.
     *
     * @param ParameterInterface[] $parameters
     * @param ParameterInterface   $returnValue
     */
    public function __construct($target, ?string $serviceName, string $methodName, array $arguments, array $parameters, ParameterInterface $returnValue)
    {
        parent::__construct($target, $serviceName, $methodName, $arguments);
        $this->parameters = $parameters;
        $this->returnValue = $returnValue;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getReturnValue(): ParameterInterface
    {
        return $this->returnValue;
    }
}
