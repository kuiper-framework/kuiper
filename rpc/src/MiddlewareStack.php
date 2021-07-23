<?php

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
        $next = function (RpcRequestInterface $request) use ($index): RpcResponseInterface {
            return $this->callNext($request, $index + 1);
        };
        $handler = new class($next) implements RpcRequestHandlerInterface {
            /**
             * @var callable
             */
            private $next;

            public function __construct(callable $next)
            {
                $this->next = $next;
            }

            public function handle(RpcRequestInterface $request): RpcResponseInterface
            {
                return call_user_func($this->next, $request);
            }
        };

        return $this->middlewares[$index]->process($request, $handler);
    }
}
