<?php

namespace kuiper\rpc\server;

use Closure;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\MiddlewareStackTrait;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;
use Psr\Container\ContainerInterface;

class Server implements ServerInterface, MiddlewareInterface
{
    use MiddlewareStackTrait;

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
    private $stages = [
        self::START => 'START',
        self::CALL => 'CALL',
    ];

    public function __construct(ServiceResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function add(callable $middleware, $position = self::CALL, $id = null)
    {
        if ($this->container && $middleware instanceof Closure) {
            $middleware->bindTo($this->container);
        }
        $this->addMiddleware($middleware, $position, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serve(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->middlewareStack === null) {
            $this->addMiddleware($this, self::CALL);
            $this->buildMiddlewareStack();
        }

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

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }
}
