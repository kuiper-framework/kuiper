<?php

namespace kuiper\web;

use FastRoute\RouteCollector;
use kuiper\web\exception\HttpException;
use Psr\Http\Message\ServerRequestInterface;

class Router implements RouterInterface
{
    /**
     * @var RouteRegistrarInterface
     */
    private $routeRegistrar;

    /**
     * @var QualifiedRouter[]
     */
    private $qualifiedRouters = [];

    /**
     * @var FastRouteRouter
     */
    private $defaultRouter;

    public function __construct(RouteRegistrarInterface $routeRegistrar)
    {
        $this->routeRegistrar = $routeRegistrar;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $this->initialize();
        foreach ($this->qualifiedRouters as $matcher) {
            if ($matcher->match($request)) {
                try {
                    return $matcher->dispatch($request);
                } catch (HttpException $e) {
                    // continue next match
                }
            }
        }

        return $this->defaultRouter->dispatch($request);
    }

    private function initialize()
    {
        if ($this->defaultRouter) {
            return;
        }
        $routes = [];
        foreach ($this->routeRegistrar->getRoutes() as $route) {
            $attributes = $route->getAttributes();
            if (empty($attributes)) {
                $routes[] = $route;
                continue;
            }
            $found = false;
            foreach ($this->qualifiedRouters as $matcher) {
                if ($matcher->tryAdd($route)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $matcher = new QualifiedRouter($attributes);
                $matcher->add($route);
                $this->qualifiedRouters[] = $matcher;
            }
        }
        $this->defaultRouter = new FastRouteRouter(\FastRoute\simpleDispatcher(function ($collector) use ($routes) {
            foreach ($routes as $route) {
                /* @var RouteCollector $collector */
                $collector->addRoute($route->getMethods(), $route->getPattern(), $route);
            }
        }));
    }
}
