<?php

namespace kuiper\web;

use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use kuiper\web\exception\RouteNotFoundException;

class FastRouteUrlResolver implements UrlResolverInterface
{
    /**
     * @var RouteRegistarInterface
     */
    private $routeRegistar;

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
     * @param RouteRegistarInterface $routeRegistar
     * @param string                 $baseUri
     * @param RouteParser            $parser
     */
    public function __construct(RouteRegistarInterface $routeRegistar, $baseUri, RouteParser $parser = null)
    {
        $this->routeRegistar = $routeRegistar;
        $this->baseUri = $baseUri;
        $this->routeParser = $parser ?: new StdParser();
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
        if ($absolute) {
            if (isset($route)) {
                $attrs = $route->getAttributes();
                if (isset($attrs['host'])) {
                    $scheme = isset($attrs['scheme']) ? $attrs['scheme'] : 'http';

                    return sprintf('%s://%s%s', $scheme, $attrs['host'], $url);
                }
            }

            return $this->baseUri.$url;
        } else {
            return $url;
        }
    }

    protected function getRoutePath($route, &$data)
    {
        $pattern = $route->getPattern();

        $routeDatas = $this->routeParser->parse($pattern);
        // $routeDatas is an array of all possible routes that can be made. There is
        // one routedata for each optional parameter plus one for no optional parameters.
        //
        // The most specific is last, so we look for that first.
        $routeDatas = array_reverse($routeDatas);

        $segments = [];
        foreach ($routeDatas as $routeData) {
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

        return $url = implode('', $segments);
    }

    protected function getNamedRoute($name)
    {
        if ($this->routes === null) {
            $this->routes = [];
            foreach ($this->routeRegistar->getRoutes() as $route) {
                if ($route->getName()) {
                    $this->routes[$route->getName()] = $route;
                }
            }
        }
        if (!isset($this->routes[$name])) {
            throw new RouteNotFoundException($name);
        }

        return $this->routes[$name];
    }
}
