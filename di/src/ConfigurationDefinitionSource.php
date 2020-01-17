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
use kuiper\di\annotation\Conditional;

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
                    $definitions[] = new ConditionalDefinition($definition, $condition);
                } elseif ($configurationCondition) {
                    $definitions[] = new ConditionalDefinition($definition, $configurationCondition);
                } else {
                    $definitions[$definition->getName()] = $definition;
                }
            }
        }
        if ($configuration instanceof DefinitionConfiguration) {
            foreach ($configuration->getDefinitions() as $name => $def) {
                if ($configurationCondition) {
                    $definitions[] = new ConditionalDefinition($this->normalizeDefinition($def, $name), $configurationCondition);
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

    /**
     * @param mixed $definition
     * @param $name
     */
    private function normalizeDefinition($definition, $name): Definition
    {
        if ($definition instanceof DefinitionHelper) {
            $definition = $definition->getDefinition($name);
        } elseif ($definition instanceof \Closure) {
            $definition = new FactoryDefinition($name, $definition);
        } elseif (!$definition instanceof Definition) {
            $definition = new ValueDefinition($definition);
            $definition->setName($name);
        }

        return $definition;
    }
}
