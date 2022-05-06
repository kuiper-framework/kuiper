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
     * MiddlewareStack constructor.
     *
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(private readonly array $middlewares, private readonly RpcRequestHandlerInterface $final)
    {
    }

    public function withFinalHandler(RpcRequestHandlerInterface $final): self
    {
        return new self($this->middlewares, $final);
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
