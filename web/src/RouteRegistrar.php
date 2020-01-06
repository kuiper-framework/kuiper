<?php

namespace kuiper\web;

class RouteRegistrar implements RouteRegistrarInterface
{
    /**
     * @var array
     */
    private $routes = [];

    /**
     * @var array
     */
    private $groupStack = [];

    /**
     * @var string
     */
    private $routeClass = Route::class;

    /**
     * @var string
     */
    private $actionDelimiter = ':';

    /**
     * @var string
     */
    private $namespace;

    /**
     * Add GET route.
     *
     * @param string          $pattern The route URI pattern
     * @param callable|string $action  The route callback routine
     *
     * @return RouteInterface
     */
    public function get(string $pattern, $action)
    {
        return $this->map(['GET'], $pattern, $action);
    }

    /**
     * Add POST route.
     *
     * @param string          $pattern The route URI pattern
     * @param callable|string $action  The route callback routine
     *
     * @return RouteInterface
     */
    public function post(string $pattern, $action)
    {
        return $this->map(['POST'], $pattern, $action);
    }

    /**
     * Add PUT route.
     *
     * @param string          $pattern The route URI pattern
     * @param callable|string $action  The route callback routine
     *
     * @return RouteInterface
     */
    public function put(string $pattern, $action)
    {
        return $this->map(['PUT'], $pattern, $action);
    }

    /**
     * Add PATCH route.
     *
     * @param string          $pattern The route URI pattern
     * @param callable|string $action  The route callback routine
     *
     * @return RouteInterface
     */
    public function patch(string $pattern, $action)
    {
        return $this->map(['PATCH'], $pattern, $action);
    }

    /**
     * Add DELETE route.
     *
     * @param string          $pattern The route URI pattern
     * @param callable|string $action  The route callback routine
     *
     * @return RouteInterface
     */
    public function delete(string $pattern, $action)
    {
        return $this->map(['DELETE'], $pattern, $action);
    }

    /**
     * Add OPTIONS route.
     *
     * @param string          $pattern The route URI pattern
     * @param callable|string $action  The route callback routine
     *
     * @return RouteInterface
     */
    public function options(string $pattern, $action)
    {
        return $this->map(['OPTIONS'], $pattern, $action);
    }

    /**
     * Add route for any HTTP method.
     *
     * @param string          $pattern The route URI pattern
     * @param callable|string $action  The route callback routine
     *
     * @return RouteInterface
     */
    public function any(string $pattern, $action)
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $action);
    }

    /**
     * Add route with multiple methods.
     *
     * @param string[]        $methods Numeric array of HTTP method names
     * @param string          $pattern The route URI pattern
     * @param callable|string $action  The route callback routine
     *
     * @return RouteInterface
     */
    public function map(array $methods, string $pattern, $action)
    {
        if (empty($action)) {
            throw new \InvalidArgumentException('route callback must not be empty');
        }
        $attributes = [];
        if (!empty($this->groupStack)) {
            $attributes = end($this->groupStack);
        }
        $namespace = $this->namespace;
        if (isset($attributes['namespace'])) {
            $namespace = $attributes['namespace'];
            unset($attributes['namespace']);
        }
        /** @var RouteInterface $route */
        $route = new $this->routeClass($methods, $pattern, $this->parseAction($action, $namespace));
        if (!empty($attributes)) {
            $route->match($attributes);
        }
        $this->routes[] = $route;

        return $route;
    }

    public function group(array $attributes, \Closure $callback)
    {
        if (!empty($this->groupStack)) {
            $attributes = array_merge(end($this->groupStack), $attributes);
        }

        $this->groupStack[] = $attributes;
        $callback($this);

        array_pop($this->groupStack);
    }

    public function getRoutes() : array
    {
        return $this->routes;
    }

    public function setRouteClass($routeClass)
    {
        $this->routeClass = $routeClass;

        return $this;
    }

    public function setDefaultNamespace($ns)
    {
        $this->namespace = $ns;

        return $this;
    }

    public function setActionDelimiter($actionDelimiter)
    {
        $this->actionDelimiter = $actionDelimiter;

        return $this;
    }

    protected function addNamespace($class, $namespace)
    {
        if (empty($class)) {
            throw new \InvalidArgumentException('Invalid action controller');
        }
        if (empty($namespace) || $class[0] == '\\') {
            return $class;
        }

        return rtrim($namespace, '\\').'\\'.$class;
    }

    protected function parseAction($action, $namespace)
    {
        if (is_string($action)) {
            return $this->parseActionFromString($action, $namespace);
        } elseif (is_array($action)) {
            return $this->parseActionFromArray($action, $namespace);
        } elseif (is_callable($action)) {
            return $action;
        } else {
            throw new \InvalidArgumentException('Invalid action '.gettype($action));
        }
    }

    /**
     * @param string $action
     * @param string $namespace
     * @return mixed
     */
    protected function parseActionFromString(string $action, $namespace): array
    {
        if (($pos = strpos($action, $this->actionDelimiter)) !== false) {
            return [
                $this->addNamespace(substr($action, 0, $pos), $namespace),
                substr($action, $pos + strlen($this->actionDelimiter)) ?: null,
            ];
        } else {
            return $action;
        }
    }

    /**
     * @param array $action
     * @param string $namespace
     * @return array
     */
    protected function parseActionFromArray(array $action, $namespace)
    {
        if (isset($action['controller'])) {
            return [
                is_string($action['controller']) ? $this->addNamespace($action['controller'], $namespace) : $action['controller'],
                isset($action['action']) ? $action['action'] : null,
            ];
        } elseif (isset($action[0])) {
            return [
                is_string($action[0]) ? $this->addNamespace($action[0], $namespace) : $action[0],
                isset($action[1]) ? $action[1] : null,
            ];
        } else {
            throw new \InvalidArgumentException('Invalid action ' . gettype($action));
        }
    }
}
