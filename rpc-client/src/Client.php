<?php

namespace kuiper\rpc\client;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\MiddlewareStackTrait;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\Response;
use kuiper\rpc\ResponseInterface;

class Client implements ClientInterface, MiddlewareInterface
{
    use MiddlewareStackTrait;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var array
     */
    private $stages = [
        self::START => 'START',
        self::CALL => 'CALL',
    ];

    public function __construct(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function add(callable $middleware, $position = self::CALL, $id = null)
    {
        $this->addMiddleware($middleware, $position, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function call($wrappedClass, $method, array $params = [])
    {
        $request = new Request($wrappedClass, $method, $params);
        if ($this->middlewareStack === null) {
            $this->addMiddleware($this, self::CALL);
            $this->buildMiddlewareStack();
        }
        $response = $this->callMiddlewareStack($request, new Response());

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
