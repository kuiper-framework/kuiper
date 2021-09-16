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

namespace kuiper\rpc;

class MiddlewareStack implements RpcRequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;
    /**
     * @var RpcRequestHandlerInterface
     */
    private $final;

    /**
     * MiddlewareStack constructor.
     *
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(array $middlewares, RpcRequestHandlerInterface $final)
    {
        $this->middlewares = $middlewares;
        $this->final = $final;
    }

    public function withFinalHandler(RpcRequestHandlerInterface $final): self
    {
        $copy = clone $this;
        $copy->final = $final;

        return $copy;
    }

    public function handle(RpcRequestInterface $request): RpcResponseInterface
    {
        return $this->callNext($request, 0);
    }

    private function callNext(RpcRequestInterface $request, int $index): RpcResponseInterface
    {
        if (!isset($this->middlewares[$index])) {
            return $this->final->handle($request);
        }

        $handler = new DelegateRequestHandler(function (RpcRequestInterface $request) use ($index): RpcResponseInterface {
            return $this->callNext($request, $index + 1);
        });

        return $this->middlewares[$index]->process($request, $handler);
    }
}
