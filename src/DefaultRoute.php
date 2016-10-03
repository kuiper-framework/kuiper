<?php
namespace kuiper\web;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
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
     * @inheritDoc
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (is_array($this->handler)) {
            if (is_string($this->handler[0])) {
                $this->handler[0] = $this->container->get($this->handler[0]);
            }
            if ($this->handler[0] instanceof ControllerInterface) {
                $controller = $this->handler[0];
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
        return call_user_func($this->handler, $request, $response, $this->arguments);
    }
}