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

use Closure;
use DI\Attribute\Inject;
use DI\Definition\Definition;
use DI\Definition\FactoryDefinition;
use DI\Definition\Helper\DefinitionHelper;
use DI\Definition\ValueDefinition;
use function DI\get;
use kuiper\di\attribute\Bean;
use kuiper\helper\Text;
use kuiper\reflection\ReflectionType;
use ReflectionClass;
use ReflectionMethod;

class ConfigurationDefinitionLoader
{
    public function __construct(private ContainerBuilderInterface $containerBuilder)
    {
    }

    public function getDefinitions(object $configuration, bool $ignoreCondition = false): array
    {
        $definitions = [];
        $reflectionClass = new ReflectionClass($configuration);
        $configurationCondition = AllCondition::create($reflectionClass);
        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $reflectionAttributes = $method->getAttributes(Bean::class);
            if (empty($reflectionAttributes)) {
                continue;
            }
            /** @var Bean $bean */
            $bean = $reflectionAttributes[0]->newInstance();
            $definition = $this->createDefinition($bean, $configuration, $method);
            $condition = null;
            if (!$ignoreCondition) {
                $condition = AllCondition::create($method);
                if (null !== $configurationCondition) {
                    if (null !== $condition) {
                        $condition->addCondition($configurationCondition);
                    } else {
                        $condition = $configurationCondition;
                    }
                }
            }

            if (null !== $condition) {
                $definitions[] = new ConditionDefinition($condition, $definition);
                $this->containerBuilder->defer(function ($container) use ($condition, $definition, $method): void {
                    if ($condition->matches($container)) {
                        $this->processComponentAnnotation($definition->getName(), $method);
                    }
                });
            } else {
                $definitions[$definition->getName()] = $definition;
                $this->processComponentAnnotation($definition->getName(), $method);
            }
        }
        if ($configuration instanceof DefinitionConfiguration) {
            foreach ($configuration->getDefinitions() as $name => $def) {
                if (null !== $configurationCondition && !$ignoreCondition) {
                    $definitionResolver = $this->createDefinitionResolver($def, (string) $name);
                    $definitions[] = new ConditionDefinition($configurationCondition, $definitionResolver, $name);
                } else {
                    $definitions[$name] = $def;
                }
            }
        }

        return $definitions;
    }

    private function createDefinition(Bean $beanAnnotation, object $configuration, ReflectionMethod $method): FactoryDefinition
    {
        $name = $beanAnnotation->getName();
        if (Text::isEmpty($name)) {
            $returnType = ReflectionType::fromPhpType($method->getReturnType());
            if ($returnType->isClass()) {
                $name = $returnType->getName();
            } else {
                $name = $method->getName();
            }
        }

        return new FactoryDefinition($name, [$configuration, $method->getName()], $this->resolveMethodParameters($method));
    }

    private function resolveMethodParameters(ReflectionMethod $method): array
    {
        $parameters = [];
        $attributes = $method->getAttributes(Inject::class);
        if (!empty($attributes)) {
            /** @var Inject $inject */
            $inject = $attributes[0]->newInstance();
            foreach ($inject->getParameters() as $key => $parameter) {
                $parameters[$key] = get($parameter);
            }
        }
        foreach ($method->getParameters() as $parameter) {
            $attributes = $parameter->getAttributes(Inject::class);
            if (!empty($attributes)) {
                /** @var Inject $inject */
                $inject = $attributes[0]->newInstance();
                $parameters[$parameter->getName()] = get($inject->getName());
            }
        }

        return $parameters;
    }

    private function createDefinitionResolver(mixed $definition, string $name): callable
    {
        return static function () use ($definition, $name): Definition {
            if ($definition instanceof DefinitionHelper) {
                $definition = $definition->getDefinition($name);
            } elseif ($definition instanceof Closure) {
                $definition = new FactoryDefinition($name, $definition);
            } elseif ($definition instanceof Definition) {
                $definition->setName($name);
            } else {
                $definition = new ValueDefinition($definition);
                $definition->setName($name);
            }

            return $definition;
        };
    }

    private function processComponentAnnotation(string $name, ReflectionMethod $method): void
    {
        $returnType = ReflectionType::fromPhpType($method->getReturnType());
        if ($returnType->isClass()) {
            if (!class_exists($returnType->getName())) {
                return;
            }
            try {
                $reflectionClass = new ReflectionClass($returnType->getName());
                foreach ($reflectionClass->getAttributes(Component::class, \ReflectionAttribute::IS_INSTANCEOF) as $reflectionAttribute) {
                    /** @var Component $attribute */
                    $attribute = $reflectionAttribute->newInstance();
                    $attribute->setTarget($reflectionClass);
                    $attribute->setComponentId($name);
                    if ($attribute instanceof ContainerBuilderAwareInterface) {
                        $attribute->setContainerBuilder($this->containerBuilder);
                    }
                    $attribute->handle();
                }
            } catch (\ReflectionException $e) {
                trigger_error("ReflectionClass on $returnType failed");
            }
        }
    }
}
