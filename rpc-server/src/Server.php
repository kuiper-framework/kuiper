<?php

namespace kuiper\rpc\server;

use Closure;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\MiddlewareStack;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;
use Psr\Container\ContainerInterface;

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
     * @var MiddlewareStack
     */
    private $middlewares;

    public function __construct(ServiceResolverInterface $resolver)
    {
        $this->resolver = $resolver;
        $this->middlewares = new MiddlewareStack([
            self::START => 'START',
            self::CALL => 'CALL',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function add(callable $middleware, $position = self::CALL, $id = null)
    {
        if ($this->container && $middleware instanceof Closure) {
            $middleware->bindTo($this->container);
        }
        $this->middlewares->addMiddleware($middleware, $position, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serve(RequestInterface $request, ResponseInterface $response)
    {
        if (!$this->middlewares->isInitialized()) {
            $this->middlewares->addMiddleware($this, self::CALL);
            $this->middlewares->initialize();
        }

        return $this->middlewares->callMiddlewareStack($request, $response);
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

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }
}
