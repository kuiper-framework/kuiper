<?php

declare(strict_types=1);

namespace kuiper\web;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Controller;
use kuiper\di\ComponentCollection;
use kuiper\helper\Text;
use kuiper\web\annotation\filter\FilterInterface;
use kuiper\web\annotation\RequestMapping;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;

class AnnotationProcessor implements AnnotationProcessorInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;
    /**
     * @var RouteCollectorProxyInterface
     */
    private $routeCollector;

    /**
     * @var string|null
     */
    private $contextUrl;
    /**
     * @var string|null
     */
    private $namespace;

    public function __construct(
        ContainerInterface $container,
        AnnotationReaderInterface $annotationReader,
        RouteCollectorProxyInterface $routeCollector,
        ?string $contextUrl = null,
        ?string $namespace = null)
    {
        $this->container = $container;
        $this->annotationReader = $annotationReader;
        $this->routeCollector = $routeCollector;
        $this->contextUrl = $contextUrl;
        $this->namespace = $namespace;
    }

    public function process(): void
    {
        $seen = [];
        foreach (ComponentCollection::getAnnotations(Controller::class) as $annotation) {
            /** @var Controller $annotation */
            $controllerClass = $annotation->getTarget();
            if (null !== $this->namespace && !Text::startsWith($controllerClass->getNamespaceName(), $this->namespace)) {
                continue;
            }
            if (isset($seen[$controllerClass->getName()])) {
                continue;
            }
            $seen[$controllerClass->getName()] = true;
            $prefix = $this->contextUrl;
            /** @var RequestMapping|null $requestMapping */
            $requestMapping = $this->annotationReader->getClassAnnotation($controllerClass, RequestMapping::class);
            if (null !== $requestMapping) {
                $prefix .= $requestMapping->value;
            }
            if (null === $prefix || '' === $prefix) {
                $this->addMapping($this->routeCollector, $controllerClass);
            } else {
                $self = $this;
                $this->routeCollector->group($prefix, function (RouteCollectorProxyInterface $group) use ($self, $controllerClass): void {
                    $self->addMapping($group, $controllerClass);
                });
            }
        }
    }

    private function addMapping(RouteCollectorProxyInterface $routeCollector, \ReflectionClass $controllerClass): void
    {
        $controller = $this->container->get($controllerClass->getName());
        foreach ($controllerClass->getMethods() as $reflectionMethod) {
            if (!$reflectionMethod->isPublic() || $reflectionMethod->isStatic()) {
                continue;
            }
            /** @var RequestMapping|null $mapping */
            $mapping = $this->annotationReader->getMethodAnnotation($reflectionMethod, RequestMapping::class);
            if (null !== $mapping) {
                foreach ((array) $mapping->value as $pattern) {
                    if (isset($mapping->method)) {
                        $route = $routeCollector->map($mapping->method, $pattern, [$controller, $reflectionMethod->getName()]);
                    } else {
                        $route = $routeCollector->any($pattern, [$controller, $reflectionMethod->getName()]);
                    }
                    $this->addRouteMiddleware($route, $reflectionMethod);
                    if (Text::isNotEmpty($mapping->name)) {
                        if (is_array($mapping->value)) {
                            throw new \InvalidArgumentException('Cannot set route name when there multiple routes for method '.$reflectionMethod->getDeclaringClass().'::'.$reflectionMethod->getName());
                        }
                        $route->setName($mapping->name);
                    }
                }
            }
        }
    }

    /**
     * @param RouteGroupInterface|RouteInterface $route
     */
    private function addRouteMiddleware($route, \ReflectionMethod $method): void
    {
        /** @var FilterInterface[] $filters */
        $filters = [];
        foreach ($this->annotationReader->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof FilterInterface) {
                $filters[get_class($annotation)] = $annotation;
            }
        }
        foreach ($this->annotationReader->getClassAnnotations($method->getDeclaringClass()) as $annotation) {
            if ($annotation instanceof FilterInterface
                && !isset($filters[get_class($annotation)])) {
                $filters[get_class($annotation)] = $annotation;
            }
        }
        if (!empty($filters)) {
            usort($filters, static function (FilterInterface $a, FilterInterface $b): int {
                return $a->getPriority() - $b->getPriority();
            });
            foreach ($filters as $filter) {
                $middleware = $filter->createMiddleware($this->container);
                if (null !== $middleware) {
                    $route->add($middleware);
                }
            }
        }
    }
}
