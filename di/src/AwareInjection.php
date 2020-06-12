<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\ObjectDefinition;
use DI\Definition\ObjectDefinition\MethodInjection;
use DI\Definition\Reference;

class AwareInjection
{
    /**
     * @var string
     */
    private $awareInterfaceName;

    /**
     * @var string
     */
    private $setter;

    /**
     * @var callable
     */
    private $methodInjectionFactory;

    /**
     * AwareInterface constructor.
     */
    public function __construct(string $awareInterfaceName, string $setter, callable $methodInjectionFactory)
    {
        $this->awareInterfaceName = $awareInterfaceName;
        $this->setter = $setter;
        $this->methodInjectionFactory = $methodInjectionFactory;
    }

    public function getInterfaceName(): string
    {
        return $this->awareInterfaceName;
    }

    public function match(string $className): bool
    {
        return is_a($className, $this->awareInterfaceName, true);
    }

    public function inject(ObjectDefinition $definition): void
    {
        foreach ($definition->getMethodInjections() as $injection) {
            if ($injection->getMethodName() === $this->setter) {
                return;
            }
        }
        $definition->addMethodInjection(call_user_func($this->methodInjectionFactory, $definition));
    }

    public static function create(string $awareInterfaceName): AwareInjection
    {
        $reflectionClass = new \ReflectionClass($awareInterfaceName);
        $methods = $reflectionClass->getMethods();
        if (count($methods) > 1) {
            throw new \InvalidArgumentException("$awareInterfaceName has more than one method");
        }
        $method = $methods[0];
        $parameters = $method->getParameters();
        if (count($parameters) > 1) {
            throw new \InvalidArgumentException("$awareInterfaceName::{$method->getName()} has more than one parameter");
        }
        $parameter = $parameters[0];
        if ($parameter->getType()->isBuiltin()) {
            throw new \InvalidArgumentException("$awareInterfaceName::{$method->getName()} parameter {$parameter->getName()} should has class type");
        }
        $setter = $method->getName();
        $beanName = $parameter->getType()->getName();

        return new self($awareInterfaceName, $setter, function ($defintion) use ($setter, $beanName) {
            return new MethodInjection($setter, [new Reference($beanName)]);
        });
    }
}
