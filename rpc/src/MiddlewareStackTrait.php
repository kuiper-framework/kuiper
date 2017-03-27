<?php

namespace kuiper\rpc;

trait MiddlewareStackTrait
{
    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @var array
     */
    private $middlewareStack;

    /**
     * @var string[]
     */
    private $stages;

    protected function addMiddleware(callable $middleware, $position, $id = null)
    {
        if (is_int($position)) {
            if (!isset($this->stages[$position])) {
                throw new InvalidArgumentException("Invalid position '{$position}'");
            }
            $this->middlewares[$position][] = [$id, $middleware];
        } elseif (is_string($position)) {
            if (strpos($position, 'before:') === 0) {
                $before = true;
                $position = substr($position, 7 /*strlen('before:')*/);
            } elseif (strpos($position, 'after:') === 0) {
                $before = false;
                $position = substr($position, 6 /*strlen('after:')*/);
            } else {
                throw new InvalidArgumentException("Invalid position '{$position}', expects 'before:ID' or 'after:ID'");
            }
            if (($position = array_search(strtoupper($position), $this->stages)) !== false) {
                if ($position === count($this->stages) - 1 && !$before) {
                    throw new InvalidArgumentException('Cannot add middleware after call');
                }
                $this->middlewares[$position][] = [$id, $middleware];
            } else {
                $found = false;
                foreach ($this->middlewares as $stage => &$stageMiddlewares) {
                    foreach ($stageMiddlewares as $i => $scope) {
                        if ($position === $scope[0]) {
                            array_splice($stageMiddlewares, ($before ? $i : $i + 1), 0, [[$id, $middleware]]);
                            $found = true;
                            break 2;
                        }
                    }
                }
                if (!$found) {
                    throw new InvalidArgumentException("Middleware '{$position}' was not registered");
                }
            }
        }
    }

    protected function callMiddlewareStack(RequestInterface $request, ResponseInterface $response, $i = 0)
    {
        if ($i < count($this->middlewareStack)) {
            $middleware = $this->middlewareStack[$i];

            return $middleware($request, $response, function ($request, $response) use ($i) {
                return $this->callMiddlewareStack($request, $response, $i + 1);
            });
        } else {
            return $response;
        }
    }

    protected function buildMiddlewareStack()
    {
        $middlewares = [];
        foreach ($this->stages as $stage => $name) {
            if (isset($this->middlewares[$stage])) {
                foreach ($this->middlewares[$stage] as $scope) {
                    $middlewares[] = $scope[1];
                }
            }
        }
        $this->middlewareStack = $middlewares;
    }
}
