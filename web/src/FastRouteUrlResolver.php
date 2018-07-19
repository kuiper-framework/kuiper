<?php

namespace kuiper\web;

use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use kuiper\web\exception\RouteNotFoundException;

class FastRouteUrlResolver implements UrlResolverInterface
{
    use RequestAwareTrait;

    /**
     * @var RouteRegistrarInterface
     */
    private $routeRegistrar;

    /**
     * @var string
     */
    private $baseUri;

    /**
     * @var RouteParser
     */
    private $routeParser;

    /**
     * @var array
     */
    private $routes;

    /**
     * Constructs url resolver.
     *
     * @param RouteRegistrarInterface $routeRegistrar
     * @param string                  $baseUri
     * @param RouteParser             $parser
     */
    public function __construct(RouteRegistrarInterface $routeRegistrar, $baseUri = null, RouteParser $parser = null)
    {
        $this->routeRegistrar = $routeRegistrar;
        $this->setBaseUri($baseUri);
        $this->routeParser = $parser ?: new StdParser();
    }

    public function setBaseUri($baseUri)
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name, array $data = [], $absolute = false)
    {
        if (!is_string($name) || empty($name)) {
            throw new \InvalidArgumentException("Invalid uri for name '{$name}'");
        }
        if ($name[0] == '/') {
            $url = $name;
        } else {
            $route = $this->getNamedRoute($name);
            $url = $this->getRoutePath($route, $data);
        }

        if (!empty($data)) {
            $url .= '?'.http_build_query($data);
        }
        if (isset($route)) {
            $url = $this->buildUrlFromRoute($route, $url);
        }
        if ($absolute) {
            return ($this->getBaseUri() ?: $this->getBaseUriFromRequest()).$url;
        } else {
            return $url;
        }
    }

    protected function getRoutePath(RouteInterface $route, &$data)
    {
        $pattern = $route->getPattern();

        $routeSegments = $this->routeParser->parse($pattern);
        // $routeSegments is an array of all possible routes that can be made. There is
        // one route data for each optional parameter plus one for no optional parameters.
        //
        // The most specific is last, so we look for that first.
        $routeSegments = array_reverse($routeSegments);

        $segments = [];
        $segmentName = null;
        foreach ($routeSegments as $routeData) {
            $vars = $data;
            foreach ($routeData as $item) {
                if (is_string($item)) {
                    // this segment is a static string
                    $segments[] = $item;
                    continue;
                }

                // This segment has a parameter: first element is the name
                if (!array_key_exists($item[0], $vars)) {
                    // we don't have a data element for this segment: cancel
                    // testing this routeData item, so that we can try a less
                    // specific routeData item.
                    $segments = [];
                    $segmentName = $item[0];
                    break;
                }
                $segments[] = $vars[$item[0]];
                unset($vars[$item[0]]);
            }
            if (!empty($segments)) {
                $data = $vars;
                // we found all the parameters for this route data, no need to check
                // less specific ones
                break;
            }
        }

        if (empty($segments)) {
            throw new \InvalidArgumentException('Missing data for URL segment: '.$segmentName);
        }

        return implode('', $segments);
    }

    protected function getNamedRoute($name): RouteInterface
    {
        if ($this->routes === null) {
            $this->routes = [];
            foreach ($this->routeRegistrar->getRoutes() as $route) {
                if ($route->getName()) {
                    $this->routes[$route->getName()] = $route;
                }
            }
        }
        if (!isset($this->routes[$name])) {
            throw new RouteNotFoundException("Route does not exist for name '{$name}'");
        }

        return $this->routes[$name];
    }

    /**
     * @param RouteInterface $route
     * @param string         $url
     *
     * @return string
     */
    private function buildUrlFromRoute($route, $url)
    {
        $attributes = $route->getAttributes();
        if (isset($attributes['prefix'])) {
            return $attributes['prefix'].$url;
        } else {
            return $url;
        }
    }

    private function getBaseUriFromRequest()
    {
        $uri = $this->request->getUri();
        $port = $uri->getPort();

        return sprintf('%s://%s', $uri->getScheme(), $uri->getHost())
            .(isset($port) && $port != 80 ? ':'.$port : '');
    }
}
