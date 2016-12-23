<?php
namespace kuiper\web;

use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use RuntimeException;
use InvalidArgumentException;

class FastRouteUrlResolver implements UrlResolverInterface
{
    /**
     * @var array
     */
    private $routes;

    /**
     * @var string
     */
    private $baseUri;

    /**
     * @var RouteParser
     */
    private $routeParser;

    /**
     * Constructs url resolver
     *
     * @param RouteSourceInterface $routes
     * @param string $baseUri
     * @param RouteParser $parser
     */
    public function __construct(RouteSourceInterface $routes, $baseUri = null, RouteParser $parser = null)
    {
        $this->routes = $routes;
        $this->baseUri = $baseUri;
        $this->routeParser = $parser ?: new StdParser;
    }

    /**
     * @inheritDoc
     */
    public function get($name, array $data)
    {
        $route = $this->getNamedRoute($name);
        $pattern = $route->getPattern();

        $routeDatas = $this->routeParser->parse($pattern);
        // $routeDatas is an array of all possible routes that can be made. There is
        // one routedata for each optional parameter plus one for no optional parameters.
        //
        // The most specific is last, so we look for that first.
        $routeDatas = array_reverse($routeDatas);

        $segments = [];
        foreach ($routeDatas as $routeData) {
            foreach ($routeData as $item) {
                if (is_string($item)) {
                    // this segment is a static string
                    $segments[] = $item;
                    continue;
                }

                // This segment has a parameter: first element is the name
                if (!array_key_exists($item[0], $data)) {
                    // we don't have a data element for this segment: cancel
                    // testing this routeData item, so that we can try a less
                    // specific routeData item.
                    $segments = [];
                    $segmentName = $item[0];
                    break;
                }
                $segments[] = $data[$item[0]];
                unset($data[$item[0]]);
            }
            if (!empty($segments)) {
                // we found all the parameters for this route data, no need to check
                // less specific ones
                break;
            }
        }

        if (empty($segments)) {
            throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
        }
        $url = implode('', $segments);

        if (!empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        return $this->baseUri ? $this->baseUri . $url : $url;
    }

    protected function getNamedRoute($name)
    {
        foreach ($this->routes->getRoutes() as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }
        throw new RuntimeException('Named route does not exist for name: ' . $name);
    }
}