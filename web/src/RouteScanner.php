<?php
namespace kuiper\web;

use kuiper\annotations\ReaderInterface;
use kuiper\helper\Text;
use kuiper\reflection\ReflectionNamespaceFactoryInterface;
use kuiper\web\annotation\route\Route;
use kuiper\web\annotation\route\RoutePrefix;
use ReflectionClass;

class RouteScanner
{
    /**
     * @var ReaderInterface
     */
    private $annotationReader;
    
    /**
     * @var ReflectionNamespaceFactoryInterface
     */
    private $reflectionNamespaceFactory;

    /**
     * @var string
     */
    private $controllerSuffix;

    /**
     * @var string
     */
    private $actionSuffix;

    public function __construct(
        ReaderInterface $reader,
        ReflectionNamespaceFactoryInterface $reflectionNamespaceFactory,
        $controllerSuffix = 'Controller',
        $actionSuffix = 'Action'
    ) {
        $this->annotationReader = $reader;
        $this->reflectionNamespaceFactory = $reflectionNamespaceFactory;
        $this->controllerSuffix = $controllerSuffix;
        $this->actionSuffix = $actionSuffix;
    }
    
    public function scan($namespaces)
    {
        if (is_string($namespaces)) {
            $namespaces = [$namespaces];
        }
        $seen = [];
        $routes = [];
        foreach ($namespaces as $namespace) {
            $reflNs = $this->reflectionNamespaceFactory->create($namespace);
            foreach ($reflNs->getClasses() as $className) {
                if ($this->controllerSuffix && !Text::endsWith($className, $this->controllerSuffix)) {
                    continue;
                }
                if (isset($seen[$className])) {
                    continue;
                }
                $seen[$className] = true;
                $class = new ReflectionClass($className);
                $prefix = null;
                foreach ($this->annotationReader->getClassAnnotations($class) as $annot) {
                    if ($annot instanceof RoutePrefix) {
                        $prefix = rtrim($annot->value, '/');
                    } elseif ($annot instanceof Route) {
                        $routes[] = $this->createRoute($annot, $prefix, $className, null);
                    }
                }
                foreach ($class->getMethods() as $method) {
                    if ($method->isStatic() || !$method->isPublic()) {
                        continue;
                    }
                    foreach ($this->annotationReader->getMethodAnnotations($method) as $annot) {
                        if ($annot instanceof Route) {
                            $routes[] = $this->createRoute($annot, $prefix, $className, $method->getName());
                        }
                    }
                }
            }
        }
        usort($routes, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        return $routes;
    }

    private function createRoute($route, $prefix, $controller, $action)
    {
        return [
            'annotation' => $route,
            'priority' => $route->priority,
            'pattern' => $prefix . $route->value,
            'methods' => empty($route->methods) ? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] : $route->methods,
            'name' => $route->name,
            'controller' => $controller,
            'action' => $action,
            'actionSuffix' => $this->actionSuffix
        ];
    }
}