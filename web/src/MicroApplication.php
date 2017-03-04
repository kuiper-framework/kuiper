<?php

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class MicroApplication extends Application implements RouteRegistarInterface
{
    use RouteRegistarTrait;

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
        if ($this->getContainer()->has(RouterInterface::class)) {
            return $this->getContainer()->get(RouterInterface::class);
        } else {
            return new Router($this);
        }
    }
}
