<?php

namespace kuiper\rpc;

class MiddlewareStack
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
     * @var array
     */
    private $stages;

    public function __construct(array $stages = [])
    {
        $this->stages = $stages;
    }

    public function addMiddleware(callable $middleware, $position, $id = null)
    {
        if ($this->isInitialized()) {
            throw new \RuntimeException('middlewares is initialized, cannot modify');
        }
        if (is_int($position)) {
            $this->addMiddlewareByPosition($middleware, $position, $id);
        } elseif (is_string($position)) {
            $this->addMiddlewareByName($middleware, $position, $id);
        }
    }

    public function callMiddlewareStack(RequestInterface $request, ResponseInterface $response, $index = 0)
    {
        $this->initialize();
        if ($index < count($this->middlewareStack)) {
            $middleware = $this->middlewareStack[$index];

            return $middleware($request, $response, function ($request, $response) use ($index) {
                return $this->callMiddlewareStack($request, $response, $index + 1);
            });
        } else {
            return $response;
        }
    }

    public function isInitialized()
    {
        return isset($this->middlewareStack);
    }

    public function initialize()
    {
        if ($this->isInitialized()) {
            return;
        }
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

    /**
     * @param callable $middleware
     * @param int      $position
     * @param string   $id
     */
    private function addMiddlewareByPosition(callable $middleware, $position, $id)
    {
        if (!isset($this->stages[$position])) {
            throw new \InvalidArgumentException("Invalid position '{$position}'");
        }
        $this->middlewares[$position][] = [$id, $middleware];
    }

    /**
     * @param callable $middleware
     * @param string   $position
     * @param string   $id
     */
    private function addMiddlewareByName(callable $middleware, $position, $id)
    {
        if (0 === strpos($position, 'before:')) {
            $before = true;
            $position = substr($position, 7 /*strlen('before:')*/);
        } elseif (0 === strpos($position, 'after:')) {
            $before = false;
            $position = substr($position, 6 /*strlen('after:')*/);
        } else {
            throw new \InvalidArgumentException("Invalid position '{$position}', expects 'before:ID' or 'after:ID'");
        }
        if (false !== ($key = array_search(strtoupper($position), $this->stages))) {
            if ($key === count($this->stages) - 1 && !$before) {
                throw new \InvalidArgumentException('Cannot add middleware after kernel call');
            }
            $this->middlewares[$key][] = [$id, $middleware];
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
                throw new \InvalidArgumentException("Middleware '{$position}' was not registered");
            }
        }
    }
}
