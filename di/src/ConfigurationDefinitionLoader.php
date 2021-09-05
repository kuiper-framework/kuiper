<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Annotation\Inject;
use DI\Definition\Definition;
use DI\Definition\FactoryDefinition;
use DI\Definition\Helper\DefinitionHelper;
use DI\Definition\ValueDefinition;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ComponentInterface;
use kuiper\helper\Text;

class ConfigurationDefinitionLoader
{
    /**
     * @var ContainerBuilderInterface
     */
    private $containerBuilder;
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    public function __construct(ContainerBuilderInterface $containerBuilder, AnnotationReaderInterface $annotationReader)
    {
        $this->containerBuilder = $containerBuilder;
        $this->annotationReader = $annotationReader;
    }

    public function getDefinitions(object $configuration, bool $ignoreCondition = false): array
    {
        $definitions = [];
        $reflectionClass = new \ReflectionClass($configuration);
        $configurationCondition = AllCondition::create($this->annotationReader, $reflectionClass);
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            /** @var Bean|null $beanAnnotation */
            $beanAnnotation = $this->annotationReader->getMethodAnnotation($method, Bean::class);
            if (null === $beanAnnotation) {
                continue;
            }
            $definition = $this->createDefinition($beanAnnotation, $configuration, $method);
            $condition = null;
            if (!$ignoreCondition) {
                $condition = AllCondition::create($this->annotationReader, $method);
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
                    $definitions[] = new ConditionDefinition($configurationCondition, $this->createDefinitionResolver($def, (string) $name), $name);
                } else {
                    $definitions[$name] = $def;
                }
            }
        }

        return $definitions;
    }

    private function getMethodParameterInjections(Inject $annotation): array
    {
        $parameters = [];
        foreach ($annotation->getParameters() as $key => $parameter) {
            $parameters[$key] = \DI\get($parameter);
        }

        return $parameters;
    }

    private function createDefinition(Bean $beanAnnotation, object $configuration, \ReflectionMethod $method): FactoryDefinition
    {
        $name = $beanAnnotation->name;
        if (Text::isEmpty($name)) {
            if (null !== $method->getReturnType() && !$method->getReturnType()->isBuiltin()) {
                /** @phpstan-ignore-next-line */
                $name = $method->getReturnType()->getName();
            } else {
                $name = $method->getName();
            }
        }

        /** @var Inject|null $annotation */
        $annotation = $this->annotationReader->getMethodAnnotation($method, Inject::class);
        if (null !== $annotation) {
            return new FactoryDefinition(
                $name, [$configuration, $method->getName()], $this->getMethodParameterInjections($annotation)
            );
        }

        return new FactoryDefinition($name, [$configuration, $method->getName()]);
    }

    /**
     * @param mixed  $definition
     * @param string $name
     *
     * @return callable
     */
    private function createDefinitionResolver($definition, string $name): callable
    {
        return static function () use ($definition, $name) {
            if ($definition instanceof DefinitionHelper) {
                $definition = $definition->getDefinition($name);
            } elseif ($definition instanceof \Closure) {
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

    private function processComponentAnnotation(string $name, \ReflectionMethod $method): void
    {
        $returnType = $method->getReturnType();
        if (null !== $returnType && !$returnType->isBuiltin()) {
            /** @phpstan-ignore-next-line */
            $className = $returnType->getName();
            if (!class_exists($className)) {
                return;
            }
            $reflectionClass = new \ReflectionClass($className);
            foreach ($this->annotationReader->getClassAnnotations($reflectionClass) as $annotation) {
                if ($annotation instanceof ComponentInterface) {
                    $annotation->setTarget($reflectionClass);
                    $annotation->setComponentId($name);
                    if ($annotation instanceof ContainerBuilderAwareInterface) {
                        $annotation->setContainerBuilder($this->containerBuilder);
                    }
                    $annotation->handle();
                }
            }
        }
    }
}
