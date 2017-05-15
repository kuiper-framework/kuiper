<?php

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class MicroApplication extends Application implements RouteRegistarInterface
{
    use RouteRegistarTrait;

    /**
     * @var RouterInterface
     */
    protected $router;

    protected function getRequest()
    {
        if ($this->getContainer()->has(ServerRequestInterface::class)) {
            return $this->getContainer()->get(ServerRequestInterface::class);
        } else {
            return ServerRequestFactory::fromGlobals();
        }
    }

    protected function getResponse()
    {
        if ($this->getContainer()->has(ResponseInterface::class)) {
            return $this->getContainer()->get(ResponseInterface::class);
        } else {
            return new Response();
        }
    }

    protected function getRouter()
    {
        if ($this->router === null) {
            if ($this->getContainer()->has(RouterInterface::class)) {
                $router = $this->getContainer()->get(RouterInterface::class);
            } else {
                $router = new Router($this);
            }
            $this->setRouter($router);
        }

        return $this->router;
    }

    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;

        return $this;
    }
}
