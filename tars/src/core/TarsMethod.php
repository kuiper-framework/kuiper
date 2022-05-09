<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\rpc\RpcMethod;
use kuiper\rpc\ServiceLocator;
use kuiper\rpc\ServiceLocatorImpl;

class TarsMethod extends RpcMethod implements TarsMethodInterface
{
    public function __construct(
        $target, string $servantName, string $methodName, array $arguments,
        private readonly array $parameters,
        private readonly ParameterInterface $returnValue)
    {
        parent::__construct($target, new ServiceLocatorImpl($servantName), $methodName, $arguments);
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
