<?php

declare(strict_types=1);

namespace kuiper\rpc;

class MiddlewareStack implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;
    /**
     * @var RequestHandlerInterface
     */
    private $final;

    /**
     * MiddlewareStack constructor.
     *
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(array $middlewares, RequestHandlerInterface $final)
    {
        $this->middlewares = $middlewares;
        $this->final = $final;
    }

    public function withFinalHandler(RequestHandlerInterface $final): self
    {
        $copy = clone $this;
        $copy->final = $final;

        return $copy;
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->callNext($request, 0);
    }

    private function callNext(RequestInterface $request, int $index): ResponseInterface
    {
        if (!isset($this->middlewares[$index])) {
            return $this->final->handle($request);
        }
        $next = function (RequestInterface $request) use ($index): ResponseInterface {
            return $this->callNext($request, $index + 1);
        };
        $handler = new class($next) implements RequestHandlerInterface {
            /**
             * @var callable
             */
            private $next;

            public function __construct(callable $next)
            {
                $this->next = $next;
            }

            public function handle(RequestInterface $request): ResponseInterface
            {
                return call_user_func($this->next, $request);
            }
        };

        return $this->middlewares[$index]->process($request, $handler);
    }
}
