<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\ObjectDefinition;
use DI\Definition\ObjectDefinition\MethodInjection;
use DI\Definition\Reference;
use kuiper\reflection\ReflectionType;

class AwareInjection
{
    /**
     * @var callable
     */
    private $methodInjectionFactory;

    public function __construct(
        private  readonly string $awareInterfaceName,
        private  readonly string $setter,
        callable $methodInjectionFactory)
    {
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
        $definition->addMethodInjection(new MethodInjection($this->setter, call_user_func($this->methodInjectionFactory, $definition)));
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
        $parameterType = ReflectionType::fromPhpType($parameter->getType());
        if (!$parameterType->isClass()) {
            throw new \InvalidArgumentException("$awareInterfaceName::{$method->getName()} parameter {$parameter->getName()} not a class");
        }
        $setter = $method->getName();
        $beanName = $parameterType->getName();

        return new self($awareInterfaceName, $setter, function () use ($beanName): array {
            return [new Reference($beanName)];
        });
    }
}
