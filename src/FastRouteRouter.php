<?php
namespace kuiper\web;

use FastRoute\Dispatcher;
use kuiper\web\exception\MethodNotAllowedException;
use kuiper\web\exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * create router use nikic\fast-route ::
 *
 *   new FastRouteRouter(\FastRoute\simpleDispatcher(function($r) {
 *        $r->addRoute("GET", "/users", "MyController:getUsers");
 *        $r->addRoute(["GET", "POST"], "/user/{id:\d+}", [
 *           "controller" => "MyController",
 *           "action" => "getUser"
 *        ]);
 *   }));
 *
 *   new FastRouteRouter(\FastRoute\simpleDispatcher(function($r) {
 *        foreach ($routeScanner->scan($namespaces) as $options) {
 *            $r->addRoute($options['methods'], $options['pattern'], $options);
 *        }
 *   }));
 */
class FastRouteRouter implements RouterInterface
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;
    
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
        if ($routeInfo[0] === Dispatcher::FOUND) {
            if ($routeInfo[1] instanceof RouteInterface) {
                $route = $routeInfo[1];
            } else {
                $callback = null;
                if (is_string($routeInfo[1])) {
                    if (strpos($routeInfo[1], ':') !== false) {
                        list($controller, $action) = explode(':', $routeInfo[1]);
                        $callback = [$controller, $action];
                    } else {
                        $callback = $routeInfo[1];
                    }
                } elseif (is_array($routeInfo[1])) {
                    if (isset($routeInfo[1]['controller'])) {
                        $callback = [
                            $routeInfo[1]['controller'],
                            isset($routeInfo[1]['action']) ? $routeInfo[1]['action'] : null
                        ];
                    } elseif (isset($routeInfo[1]['handler'])) {
                        $callback = $routeInfo[1]['handler'];
                    }
                } else {
                    $callback = $routeInfo[1];
                }
                if (!isset($callback)) {
                    throw new RuntimeException("Invalid route handler " . var_export($routeInfo[1], true));
                }
                if (is_array($callback) && empty($callback[1]) && isset($routeInfo[2]['action'])) {
                    $callback[1] = $routeInfo[2]['action'];
                    unset($routeInfo[2]['action']);
                    if (isset($routeInfo[1]['actionSuffix'])) {
                        $callback[1] .= $routeInfo[1]['actionSuffix'];
                    }
                }
                $route = $this->createRoute($callback, is_array($routeInfo[1]) ? $routeInfo[1] : []);
            }
            $route->setArguments($routeInfo[2]);
            return $route;
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowedException($routeInfo[1], $request, $response);
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    protected function createRoute($handler)
    {
        $routeClass = DefaultRoute::class;
        if (isset($options['routeClass'])) {
            $routeClass = $options['routeClass'];
        }
        return new $routeClass($handler);
    }
}
