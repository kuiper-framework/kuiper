<?php

namespace kuiper\web;

use kuiper\web\exception\HttpException;
use Psr\Http\Message\ServerRequestInterface;

class Router implements RouterInterface
{
    /**
     * @var RouteRegistarInterface
     */
    private $routeRegistar;

    /**
     * @var QualifiedRouter[]
     */
    private $matchers = [];

    /**
     * @var FastRouteRouter
     */
    private $router;

    public function __construct(RouteRegistarInterface $routeRegistar)
    {
        $this->routeRegistar = $routeRegistar;
    }

    public function dispatch(ServerRequestInterface $request)
    {
        $this->initialize();
        foreach ($this->matchers as $matcher) {
            if ($matcher->match($request)) {
                try {
                    return $matcher->dispatch($request);
                } catch (HttpException $e) {
                    // continue next match
                }
            }
        }

        return $this->router->dispatch($request);
    }

    protected function initialize()
    {
        if ($this->router) {
            return;
        }
        $routes = [];
        foreach ($this->routeRegistar->getRoutes() as $route) {
            $attributes = $route->getAttributes();
            if (empty($attributes)) {
                $routes[] = $route;
                continue;
            }
            $found = false;
            foreach ($this->matchers as $matcher) {
                if ($matcher->tryAdd($route)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $matcher = new QualifiedRouter($attributes);
                $matcher->add($route);
                $this->matchers[] = $matcher;
            }
        }
        $this->router = new FastRouteRouter(\FastRoute\simpleDispatcher(function ($r) use ($routes) {
            foreach ($routes as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route);
            }
        }));
    }
}
