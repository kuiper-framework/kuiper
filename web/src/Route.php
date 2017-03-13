<?php

namespace kuiper\web;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Route implements RouteInterface
{
    /**
     * @var string|callable
     */
    private $action;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $methods;

    /**
     * @var string
     */
    private $pattern;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(array $methods, $pattern, $action)
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->action = $action;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethods(array $methods)
    {
        $route = clone $this;
        $route->methods = $methods;

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * {@inheritdoc}
     */
    public function withAction($action)
    {
        $route = clone $this;
        $route->action = $action;

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function match(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function withArguments(array $arguments)
    {
        $route = clone $this;
        $route->arguments = $arguments;

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return self
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        $action = $this->getAction();
        if ($action instanceof \Closure) {
            if ($this->container) {
                $action = $action->bindTo($this->container);
            }
        } elseif (is_array($action)) {
            if (is_string($action[0])) {
                $action[0] = $this->container->get($action[0]);
            }
            if ($action[0] instanceof ControllerInterface) {
                return $this->runController($action[0], $action[1], $request, $response);
            }
        }
        if (!is_callable($action, true)) {
            throw new \BadMethodCallException('Invalid route action '.gettype($action));
        }
        $result = call_user_func($action, $request, $response, $this->arguments);
        if ($result === null) {
            return $response;
        } elseif ($result instanceof ResponseInterface) {
            return $result;
        } else {
            throw new \BadMethodCallException('Route action should return instance of '.ResponseInterface::class);
        }
    }

    protected function runController($controller, $method, $request, $response)
    {
        if (!method_exists($controller, $method)) {
            throw new \BadMethodCallException(sprintf('Controller %s does not have method %s', get_class($controller), $method));
        }
        $controller->setRequest($request)
            ->setResponse($response);
        $result = $controller->initialize();
        if ($result === false) {
            return $controller->getResponse();
        } elseif ($result instanceof ResponseInterface) {
            return $result;
        }
        $result = call_user_func_array([$controller, $method], $this->arguments);
        if ($result === null) {
            return $controller->getResponse();
        } elseif ($result instanceof ResponseInterface) {
            return $result;
        } else {
            throw new \BadMethodCallException('Route action should return instance of '.ResponseInterface::class);
        }
    }
}
