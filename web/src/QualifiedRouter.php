<?php

namespace kuiper\web;

use FastRoute\RouteCollector;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class QualifiedRouter implements RouterInterface
{
    /**
     * @var array
     */
    private $attributes;

    /**
     * @var RouteInterface[]
     */
    private $routes;

    /**
     * @var FastRouteRouter
     */
    private $router;

    /**
     * @var callable
     */
    private $matcher;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @param RouteInterface $route
     *
     * @return bool return true when the route attributes is same to current
     */
    public function tryAdd(RouteInterface $route)
    {
        if ($route->getAttributes() == $this->attributes) {
            $this->add($route);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param RouteInterface $route
     *
     * @return static
     */
    public function add(RouteInterface $route)
    {
        $this->routes[] = $route;

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function match(ServerRequestInterface $request)
    {
        $this->initialize();

        return call_user_func($this->matcher, $request->getUri());
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $this->initialize();
        if (!empty($this->attributes['prefix'])) {
            $uri = $request->getUri();

            return $this->router->dispatch($request->withUri($uri->withPath(substr($uri->getPath(), strlen($this->attributes['prefix'])))));
        } else {
            return $this->router->dispatch($request);
        }
    }

    protected function initialize()
    {
        if ($this->router) {
            return;
        }
        $this->matcher = function (UriInterface $uri) {
            if (isset($this->attributes['scheme']) && $uri->getScheme() != $this->attributes['scheme']) {
                return false;
            }
            if (isset($this->attributes['port']) && $uri->getPort() != $this->attributes['port']) {
                return false;
            }
            if (isset($this->attributes['host']) && !fnmatch($this->attributes['host'], $uri->getHost())) {
                return false;
            }
            if (!empty($this->attributes['prefix']) && 0 !== strpos($uri->getPath(), $this->attributes['prefix'])) {
                return false;
            }

            return true;
        };
        $this->router = new FastRouteRouter(\FastRoute\simpleDispatcher(function ($collector) {
            foreach ($this->routes as $route) {
                /* @var RouteCollector $collector */
                $collector->addRoute($route->getMethods(), $route->getPattern(), $route);
            }
        }));
    }
}
