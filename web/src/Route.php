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

    public function __construct(array $methods, string $pattern, $action)
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->action = $action;
    }

    /**
     * {@inheritdoc}
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
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
    public function getMethods(): array
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
    public function getPattern(): string
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
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
    public function getArguments(): array
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
    public function run(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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

        return $this->response($result, $response);
    }

    protected function runController(ControllerInterface $controller, $method, $request, $response)
    {
        if (!method_exists($controller, $method)) {
            throw new \BadMethodCallException(sprintf('Controller %s does not have method %s', get_class($controller), $method));
        }
        $controller->setRequest($request);
        $controller->setResponse($response);
        $result = $controller->initialize();
        if (false === $result) {
            return $controller->getResponse();
        } elseif ($result instanceof ResponseInterface) {
            return $result;
        }
        $result = call_user_func_array([$controller, $method], $this->arguments);

        return $this->response($result, $controller->getResponse());
    }

    /**
     * @param ResponseInterface $result
     * @param ResponseInterface $defaultResponse
     *
     * @return ResponseInterface
     */
    private function response($result, ResponseInterface $defaultResponse): ResponseInterface
    {
        if (null === $result) {
            return $defaultResponse;
        } elseif ($result instanceof ResponseInterface) {
            return $result;
        } else {
            throw new \BadMethodCallException('Route action should return instance of '.ResponseInterface::class);
        }
    }
}
