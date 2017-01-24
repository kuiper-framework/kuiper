<?php

namespace kuiper\di\source;

use kuiper\annotations\ReaderInterface;
use kuiper\di\annotation\Component;
use kuiper\di\definition\AliasDefinition;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\reflection\ReflectionNamespaceFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use ReflectionClass;

class ComponentSource implements SourceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ReflectionNamespaceFactoryInterface
     */
    private $reflectionNamespaceFactory;

    /**
     * @var ReaderInterface
     */
    private $annotationReader;

    /**
     * @var string[]
     */
    private $namespaces;

    /**
     * @var array
     */
    private $definitions;

    public function __construct(
        array $namespaces,
        ReaderInterface $reader,
        ReflectionNamespaceFactoryInterface $reflectionNamespaceFactory = null
    ) {
        $this->reflectionNamespaceFactory = $reflectionNamespaceFactory ?: ReflectionNamespaceFactory::createInstance();
        $this->annotationReader = $reader;
        $this->namespaces = $namespaces;
    }

    public function has($name)
    {
        $this->scanComponents();

        return isset($this->definitions[$name]);
    }

    public function get($name)
    {
        $this->scanComponents();

        return isset($this->definitions[$name]) ? $this->definitions[$name] : null;
    }

    protected function scanComponents()
    {
        if (isset($this->definitions)) {
            return;
        }
        $components = $this->scanComponentsIn(array_unique($this->namespaces));
        $definitions = [];
        foreach ($components as $i => $scope) {
            $name = isset($scope['name']) ? $scope['name'] : $scope['interface'];
            if (isset($definitions[$name])) {
                $this->logger && $this->logger->debug(sprintf(
                    "[ContainerBuilder] ignore conflict component '%s' for '%s', previous was %s",
                    $scope['definition']->getAlias(),
                    $name,
                    $definitions[$name]->getAlias()
                ));
            } else {
                $definitions[$name] = $scope['definition'];
            }
        }
        $this->definitions = $definitions;
    }

    protected function scanComponentsIn($namespaces)
    {
        $seen = [];
        $components = [];
        foreach ($namespaces as $namespace) {
            $reflNs = $this->reflectionNamespaceFactory->create($namespace);
            foreach ($reflNs->getClasses() as $className) {
                if (isset($seen[$className])) {
                    continue;
                }
                $seen[$className] = true;
                $class = new ReflectionClass($className);
                $annot = $this->annotationReader->getClassAnnotation($class, Component::class);
                if ($annot === null) {
                    continue;
                }
                $definition = new AliasDefinition($className);
                if ($annot->name) {
                    $components[] = ['name' => $annot->name, 'definition' => $definition];
                } else {
                    $interfaces = $class->getInterfaceNames();
                    if (!empty($interfaces)) {
                        foreach ($interfaces as $interface) {
                            $components[] = ['interface' => $interface, 'definition' => $definition];
                        }
                    }
                }
            }
        }
        usort($components, function ($a, $b) {
            if (isset($a['name'])) {
                return 1;
            } elseif (isset($b['name'])) {
                return -1;
            }

            return 0;
        });

        return $components;
    }
}
