<?php

namespace kuiper\rpc\server;

use Interop\Container\ContainerInterface;
use kuiper\rpc\server\exception\MethodNotFoundException;
use ReflectionClass;

class ServiceResolver implements ServiceResolverInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $methods = [];

    public function add($service, $alias = null)
    {
        if ($alias === null) {
            $alias = is_object($service) ? get_class($service) : $service;
        }
        $class = new ReflectionClass($service);
        if (!is_object($service)) {
            if (!$this->container) {
                throw new \InvalidArgumentException('Invalid service');
            }
            $service = $this->container->get($service);
        }
        foreach ($class->getMethods() as $method) {
            if (!$method->isPublic() || $method->isStatic()) {
                continue;
            }
            $serviceMethod = new Method($alias.'.'.$method->getName(), [$service, $method->getName()]);
            $this->methods[$serviceMethod->getId()] = $serviceMethod;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($method)
    {
        if (!isset($this->methods[$method])) {
            throw new MethodNotFoundException("Method '$method' not found");
        }

        return $this->methods[$method];
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
