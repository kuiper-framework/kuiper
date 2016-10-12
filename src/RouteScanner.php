<?php
namespace kuiper\web;

use kuiper\reflection\ClassScanner;
use kuiper\annotations\ReaderInterface;
use kuiper\helper\Text;
use ReflectionClass;
use kuiper\web\annotation\route\RoutePrefix;
use kuiper\web\annotation\route\Route;

class RouteScanner
{
    /**
     * @var ReaderInterface 
     */
    private $annotationReader;
    
    /**
     * @var ClassScanner
     */
    private $classScanner;

    /**
     * @var string
     */
    private $controllerSuffix;

    /**
     * @var string
     */
    private $actionSuffix;

    public function __construct(ReaderInterface $reader, ClassScanner $classScanner, $controllerSuffix = 'Controller', $actionSuffix = 'Action')
    {
        $this->annotationReader = $reader;
        $this->classScanner = $classScanner;
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
            foreach ($this->classScanner->scan($namespace) as $className) {
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
        usort($routes, function($a, $b) {
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
