<?php
namespace kuiper\web;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class DefaultRoute implements RouteInterface
{
    /**
     * @var callable
     */
    private $handler;

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
    private $arguments;

    /**
     * @var ContainerInterface
     */
    private $container;
    
    public function __construct($handler)
    {
        $this->handler = $handler;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
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

    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * {@inheritdoc}
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
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
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param ContainerInterface $container
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
        if (is_array($this->handler)) {
            if (is_string($this->handler[0])) {
                $this->handler[0] = $this->container->get($this->handler[0]);
            }
            if ($this->handler[0] instanceof ControllerInterface) {
                $controller = $this->handler[0];
                if (!method_exists($controller, $this->handler[1])) {
                    throw new RuntimeException(sprintf("Controller %s does not have method %s", get_class($controller), $this->handler[1]));
                }
                $controller->setRequest($request)
                    ->setResponse($response)
                    ->initialize();
                $result = call_user_func_array($this->handler, $this->arguments);
                if ($result === null) {
                    return $controller->getResponse();
                } else {
                    return $result;
                }
            }
        }
        if (!is_callable($this->handler, true)) {
            throw new RuntimeException("Invalid route handler " . json_encode($this->handler));
        }
        $result = call_user_func($this->handler, $request, $response, $this->arguments);
        if ($result === null) {
            return $response;
        } else {
            return $result;
        }
    }
}
