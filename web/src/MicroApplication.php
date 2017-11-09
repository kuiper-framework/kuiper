<?php

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class MicroApplication extends Application implements RouteRegistrarInterface
{
    /**
     * @var RouteRegistrarInterface
     */
    protected $routeRegistrar;

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

    /**
     * @return RouteRegistrarInterface
     */
    public function getRouteRegistrar(): RouteRegistrarInterface
    {
        if (!$this->routeRegistrar) {
            if ($this->getContainer()->has(RouteRegistrarInterface::class)) {
                $this->routeRegistrar = $this->getContainer()->get(RouteRegistrarInterface::class);
            } else {
                $this->routeRegistrar = new RouteRegistrar();
            }
        }

        return $this->routeRegistrar;
    }

    /**
     * @param RouteRegistrarInterface $routeRegistrar
     *
     * @return $this
     */
    public function setRouteRegistrar(RouteRegistrarInterface $routeRegistrar)
    {
        $this->routeRegistrar = $routeRegistrar;

        return $this;
    }

    /**
     * @param RouterInterface $router
     *
     * @return $this
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $pattern, $action)
    {
        return $this->getRouteRegistrar()->get($pattern, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $pattern, $action)
    {
        return $this->getRouteRegistrar()->post($pattern, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $pattern, $action)
    {
        return $this->getRouteRegistrar()->patch($pattern, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $pattern, $action)
    {
        return $this->getRouteRegistrar()->delete($pattern, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function patch(string $pattern, $action)
    {
        return $this->getRouteRegistrar()->patch($pattern, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function options(string $pattern, $action)
    {
        return $this->getRouteRegistrar()->options($pattern, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function any(string $pattern, $action)
    {
        return $this->getRouteRegistrar()->any($pattern, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $methods, string $pattern, $action)
    {
        return $this->getRouteRegistrar()->map($methods, $pattern, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function group(array $attributes, \Closure $callback)
    {
        $this->getRouteRegistrar()->group($attributes, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return $this->getRouteRegistrar()->getRoutes();
    }
}
