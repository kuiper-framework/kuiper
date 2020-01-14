<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Annotation\Inject;
use DI\Definition\FactoryDefinition;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\Conditional;
use Psr\Container\ContainerInterface;

class ConfigurationDefinitionSource
{
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    public function __construct(AnnotationReaderInterface $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function getDefinitions($configuration): array
    {
        $definitions = [];
        $reflectionClass = new \ReflectionClass($configuration);
        /** @var Conditional $configurationCondition */
        $configurationCondition = AllCondition::create($this->annotationReader, $reflectionClass);
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            /** @var Bean $beanAnnotation */
            $beanAnnotation = $this->annotationReader->getMethodAnnotation($method, Bean::class);
            if ($beanAnnotation) {
                $definition = $this->createDefinition($beanAnnotation, $configuration, $method);
                /** @var AllCondition $condition */
                $condition = AllCondition::create($this->annotationReader, $method);

                if ($condition) {
                    if ($configurationCondition) {
                        $condition->addCondition($configurationCondition);
                    }
                    $definition = new ConditionalDefinition($definition, $condition);
                } elseif ($configurationCondition) {
                    $definition = new ConditionalDefinition($definition, $configurationCondition);
                }
                $definitions[$definition->getName()] = $definition;
            }
        }
        if ($configuration instanceof DefinitionConfiguration) {
            foreach ($configuration->getDefinitions() as $name => $definition) {
                if ($configurationCondition) {
                    $definitions[$name] = new ConditionalDefinition($definition, $configurationCondition);
                } else {
                    $definitions[$name] = $definition;
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

    private function createDefinition(Bean $beanAnnotation, $configuration, \ReflectionMethod $method): FactoryDefinition
    {
        $name = $beanAnnotation->name;
        if (!$name) {
            if ($method->getReturnType() && !$method->getReturnType()->isBuiltin()) {
                $name = $method->getReturnType()->getName();
            } else {
                $name = $method->getName();
            }
        }
        /** @var Inject $annotation */
        $annotation = $this->annotationReader->getMethodAnnotation($method, Inject::class);
        if ($annotation) {
            return new FactoryDefinition(
                $name, [$configuration, $method->getName()], $this->getMethodParameterInjections($annotation)
            );
        }

        return new FactoryDefinition($name, [$configuration, $method->getName()]);
    }
}
