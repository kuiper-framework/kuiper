<?php
namespace kuiper\web;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use InvalidArgumentException;
use Closure;

class MicroApplication extends Application implements RouteSourceInterface
{
    /**
     * @var array
     */
    private $routes = [];

    /**
     * @var string
     */
    private $routeClass = DefaultRoute::class;

    /**
     * @var string
     */
    private $defaultNamespace;
    
    /**
     * Add GET route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function get($pattern, $callable)
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * Add POST route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function post($pattern, $callable)
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * Add PUT route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function put($pattern, $callable)
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * Add PATCH route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function patch($pattern, $callable)
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * Add DELETE route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function delete($pattern, $callable)
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * Add OPTIONS route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function options($pattern, $callable)
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route for any HTTP method
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function any($pattern, $callable)
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route with multiple methods
     *
     * @param  string[] $methods  Numeric array of HTTP method names
     * @param  string   $pattern  The route URI pattern
     * @param  callable|string    $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function map(array $methods, $pattern, $callable)
    {
        if (empty($callable)) {
            throw new InvalidArgumentException("route callback must not be empty");
        }
        $container = $this->getContainer();
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($container);
        } elseif (is_string($callable) && $callable[0] !== '\\' && isset($this->defaultNamespace)) {
            $callable = $this->defaultNamespace . '\\' . $callable;
        }
        $routeClass = $this->routeClass;
        $route = new $routeClass($callable);
        $route->setPattern($pattern)
            ->setMethods($methods);
        $this->routes[] = $route;
        return $route;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    protected function getRequest()
    {
        return ServerRequestFactory::fromGlobals();
    }

    protected function getResponse()
    {
        return new Response();
    }

    protected function getRouter()
    {
        return new FastRouteRouter(\FastRoute\simpleDispatcher(function($r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route->getHandler());
            }
        }));
    }

    public function setRouteClass($routeClass)
    {
        $this->routeClass = $routeClass;
        return $this;
    }

    public function setDefaultNamespace($ns)
    {
        $this->defaultNamespace = $ns;
        return $this;
    }
}
