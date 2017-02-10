<?php

namespace kuiper\rpc\server;

use Closure;
use Interop\Container\ContainerInterface;

class Server implements ServerInterface, MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ServiceResolverInterface
     */
    private $resolver;

    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @var array
     */
    private $middlewareStack;

    public function __construct(ServiceResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function add(callable $callback)
    {
        $this->middlewares[] = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serve(RequestInterface $request, ResponseInterface $response)
    {
        return $this->callMiddlewareStack($request, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $method = $this->resolver->resolve($request->getMethod());
        $result = call_user_func_array($method->getCallable(), $request->getParameters());

        return $response->withResult($result);
    }

    protected function callMiddlewareStack(RequestInterface $request, ResponseInterface $response, $i = 0)
    {
        if ($this->middlewareStack === null) {
            $this->middlewareStack = $this->middlewares;
            $this->middlewareStack[] = $this;
        }
        if ($i < count($this->middlewareStack)) {
            $middleware = $this->middlewareStack[$i];
            if ($this->container && $middleware instanceof Closure) {
                $middleware->bindTo($this->container);
            }

            return $middleware($request, $response, function ($request, $response) use ($i) {
                return $this->callMiddlewareStack($request, $response, $i + 1);
            });
        } else {
            return $response;
        }
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }
}
