<?php

declare(strict_types=1);

namespace kuiper\rpc;

trait MiddlewareSupport
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    /**
     * @var MiddlewareStack|null
     */
    private $middlewareStack;

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        $this->middlewareStack = null;

        return $this;
    }

    private function buildMiddlewareStack(RequestHandlerInterface $finalHandler): MiddlewareStack
    {
        if (null === $this->middlewareStack) {
            $this->middlewareStack = new MiddlewareStack($this->middlewares, $finalHandler);
        }

        return $this->middlewareStack->withFinalHandler($finalHandler);
    }
}
