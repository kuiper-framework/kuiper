<?php

namespace kuiper\rpc\client;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\MiddlewareStack;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\Response;
use kuiper\rpc\ResponseInterface;

class Client implements ClientInterface, MiddlewareInterface
{
    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var MiddlewareStack
     */
    private $middlewares;

    public function __construct(HandlerInterface $handler)
    {
        $this->handler = $handler;
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
        $this->middlewares->addMiddleware($middleware, $position, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function call($wrappedClass, $method, array $params = [])
    {
        $request = new Request($wrappedClass, $method, $params);
        if (!$this->middlewares->isInitialized()) {
            $this->middlewares->addMiddleware($this, self::CALL);
            $this->middlewares->initialize();
        }
        $response = $this->middlewares->callMiddlewareStack($request, new Response());

        return $response->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $this->handler->handle($request, $response);
    }
}
