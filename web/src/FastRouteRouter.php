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
            $requestUri = $request->getMethod().' '.$request->getUri()->getPath();
            if ($routeInfo[1] instanceof RouteInterface) {
                $params = $routeInfo[2];
                $route = $routeInfo[1];
                $action = $route->getAction();
                if (is_array($action) && !isset($action[1])) {
                    if (isset($params['action'])) {
                        $method = $params['action'];
                        unset($params['action']);
                    } elseif ($this->defaultAction) {
                        $method = $this->defaultAction;
                    } else {
                        throw new \BadMethodCallException("Route matched but default action not found for '$requestUri'");
                    }

                    $action[1] = $method.(isset($attributes['action_suffix']) ? $attributes['action_suffix'] : $this->actionSuffix);
                    $route = $route->withAction($action);
                }

                return $route->withArguments($params);
            } else {
                throw new \InvalidArgumentException(sprintf(
                    "Route should be instanceof %s, gots %s for '%s'",
                    RouteInterface::class, gettype($routeInfo[1]), $requestUri
                ));
            }
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
}
