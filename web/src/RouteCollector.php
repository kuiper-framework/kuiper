<?php

namespace kuiper\web;

use kuiper\annotations\ReaderInterface;
use kuiper\helper\Text;
use kuiper\reflection\ReflectionNamespaceFactoryInterface;
use kuiper\web\annotation\route\RoutePrefix;

class RouteCollector
{
    /**
     * @var ReflectionNamespaceFactoryInterface
     */
    private $reflectionNamespaceFactory;

    /**
     * @var ReaderInterface
     */
    private $annotationReader;

    /**
     * @var RouteRegistrarInterface
     */
    private $routeRegistrar;

    /**
     * @var array
     */
    private $classes;

    /**
     * RouteCollector constructor.
     *
     * @param ReflectionNamespaceFactoryInterface $reflectionNamespaceFactory
     * @param ReaderInterface                     $annotationReader
     * @param RouteRegistrarInterface             $routeRegistrar
     */
    public function __construct(ReflectionNamespaceFactoryInterface $reflectionNamespaceFactory, ReaderInterface $annotationReader)
    {
        $this->reflectionNamespaceFactory = $reflectionNamespaceFactory;
        $this->annotationReader = $annotationReader;
    }

    public function setRouteRegistrar(RouteRegistrarInterface $routeRegistrar)
    {
        $this->routeRegistrar = $routeRegistrar;
    }

    public function addNamespace(string $namespace)
    {
        $reflectionNamespace = $this->reflectionNamespaceFactory->create($namespace);
        foreach ($reflectionNamespace->getClasses() as $className) {
            if (Text::endsWith($className, 'Controller') || $this->hasControllerAnnotation($className)) {
                $this->addClass($className);
            }
        }
    }

    public function addClass(string $className)
    {
        if (isset($this->classes[$className])) {
            return;
        }
        $this->classes[$className] = true;
        $fqcn = '\\'.ltrim($className, '\\');
        $class = new \ReflectionClass($className);
        /** @var RoutePrefix $routePrefix */
        $routePrefix = $this->annotationReader->getClassAnnotation($class, RoutePrefix::class);
        foreach ($class->getMethods() as $method) {
            if ($method->isPublic() && !$method->isStatic()) {
                /** @var \kuiper\web\annotation\route\Route $annotation */
                $annotation = $this->annotationReader->getMethodAnnotation($method, \kuiper\web\annotation\route\Route::class);
                if ($annotation) {
                    $pattern = $annotation->value;
                    if ($routePrefix) {
                        $pattern = $routePrefix->value.$pattern;
                    }
                    $route = $this->routeRegistrar->map($annotation->methods, $pattern, [$fqcn, $method->getName()]);
                    if ($annotation->name) {
                        $route->name($annotation->name);
                    }
                }
            }
        }
    }

    private function hasControllerAnnotation(string $className)
    {
        return $this->annotationReader->getClassAnnotation(new \ReflectionClass($className),
            \kuiper\web\annotation\Controller::class);
    }
}
