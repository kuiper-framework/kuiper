<?php

namespace kuiper\web;

use Psr\Http\Message\ServerRequestInterface;

class QualifiedRouter implements RouterInterface
{
    /**
     * @var array
     */
    private $attributes;

    /**
     * @var RouteInterface
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
        $code = 'true';
        foreach (['scheme', 'port'] as $property) {
            if (isset($this->attributes[$property])) {
                $code .= sprintf(' && "%s" == $uri->get%s()', addslashes($this->attributes[$property]), ucfirst($property));
            }
        }
        if (isset($this->attributes['host'])) {
            $code .= sprintf(' && fnmatch("%s", $uri->getHost())', addslashes($this->attributes['host']));
        }
        if (!empty($this->attributes['prefix'])) {
            $code .= sprintf(' && strpos($uri->getPath(), "%s") === 0', addslashes($this->attributes['prefix']));
        }
        $this->matcher = create_function('$uri', 'return '.$code.';');
        $this->router = new FastRouteRouter(\FastRoute\simpleDispatcher(function ($r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route);
            }
        }));
    }
}
