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

trait MiddlewareSupport
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $middlewares = [];

    private ?MiddlewareStack $middlewareStack = null;

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        $this->middlewareStack = null;

        return $this;
    }

    private function buildMiddlewareStack(RpcRequestHandlerInterface $finalHandler): MiddlewareStack
    {
        if (null === $this->middlewareStack) {
            $this->middlewareStack = new MiddlewareStack($this->middlewares, $finalHandler);
        }

        return $this->middlewareStack->withFinalHandler($finalHandler);
    }
}
