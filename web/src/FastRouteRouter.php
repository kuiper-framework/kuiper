<?php

namespace kuiper\web;

use FastRoute\Dispatcher;
use kuiper\web\exception\MethodNotAllowedException;
use kuiper\web\exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;

class FastRouteRouter implements RouterInterface
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var string
     */
    private $defaultAction = 'index';

    /**
     * @var string
     */
    private $actionSuffix = '';

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

        if ($routeInfo[0] === Dispatcher::FOUND) {
            return $this->getRoute($request, $routeInfo[1], $routeInfo[2]);
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowedException($routeInfo[1]);
        } else {
            throw new NotFoundException();
        }
    }

    public function getDefaultAction()
    {
        return $this->defaultAction;
    }

    public function setDefaultAction($defaultAction)
    {
        $this->defaultAction = $defaultAction;

        return $this;
    }

    public function getActionSuffix()
    {
        return $this->actionSuffix;
    }

    public function setActionSuffix($actionSuffix)
    {
        $this->actionSuffix = $actionSuffix;

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RouteInterface         $route
     * @param array                  $parameters
     *
     * @return RouteInterface
     */
    private function getRoute(ServerRequestInterface $request, $route, array $parameters)
    {
        $requestUri = $request->getMethod().' '.$request->getUri()->getPath();
        if ($route instanceof RouteInterface) {
            $action = $route->getAction();
            if (is_array($action) && !isset($action[1])) {
                $action[1] = $this->getAction($parameters);
                $route = $route->withAction($action);
            }

            return $route->withArguments($parameters);
        } else {
            throw new \InvalidArgumentException(sprintf(
                "Route should be instanceof %s, got %s for '%s'",
                RouteInterface::class, gettype($route), $requestUri
            ));
        }
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    private function getAction(array &$parameters)
    {
        if (isset($parameters['action'])) {
            $method = $parameters['action'];
            unset($parameters['action']);
        } elseif ($this->defaultAction) {
            $method = $this->defaultAction;
        } else {
            throw new \BadMethodCallException('Route matched but default action not found');
        }

        return $method.(isset($parameters['action_suffix']) ? $parameters['action_suffix'] : $this->actionSuffix);
    }
}
