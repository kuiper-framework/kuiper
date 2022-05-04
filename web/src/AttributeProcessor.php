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

namespace kuiper\web;

use kuiper\di\attribute\Controller;
use kuiper\di\ComponentCollection;
use kuiper\helper\Text;
use kuiper\web\attribute\RequestMapping;
use kuiper\web\middleware\MiddlewareFactory;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;

class AttributeProcessor implements AttributeProcessorInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly RouteCollectorProxyInterface $routeCollector,
        private readonly ?string $contextUrl = null,
        private readonly ?string $namespace = null)
    {
    }

    public function process(): void
    {
        $seen = [];
        foreach (ComponentCollection::getComponents(Controller::class) as $attribute) {
            /** @var Controller $attribute */
            /** @var \ReflectionClass $controllerClass */
            $controllerClass = $attribute->getTarget();
            if (null !== $this->namespace && !str_starts_with($controllerClass->getNamespaceName(), $this->namespace)) {
                continue;
            }
            if (isset($seen[$controllerClass->getName()])) {
                continue;
            }
            $seen[$controllerClass->getName()] = true;
            $prefix = $this->contextUrl;
            $attributes = $controllerClass->getAttributes(RequestMapping::class);
            if (count($attributes) > 0) {
                /** @var RequestMapping $requestMapping */
                $requestMapping = $attributes[0]->newInstance();
                $prefix .= $requestMapping->getMapping()[0];
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
            $attributes = $reflectionMethod->getAttributes(RequestMapping::class, \ReflectionAttribute::IS_INSTANCEOF);
            if (count($attributes) > 0) {
                /** @var RequestMapping $requestMapping */
                $requestMapping = $attributes[0];
                foreach ($requestMapping->getMapping() as $pattern) {
                    if (count($requestMapping->getMethod()) > 0) {
                        $route = $routeCollector->map($requestMapping->getMethod(), $pattern, [$controller, $reflectionMethod->getName()]);
                    } else {
                        $route = $routeCollector->any($pattern, [$controller, $reflectionMethod->getName()]);
                    }
                    $this->addRouteMiddleware($route, $reflectionMethod);
                    if (Text::isNotEmpty($requestMapping->getName())) {
                        if (count($requestMapping->getMapping()) > 1) {
                            throw new \InvalidArgumentException('Cannot set route name when there multiple routes for method '
                                . $reflectionMethod->getDeclaringClass() . '::' . $reflectionMethod->getName());
                        }
                        $route->setName($requestMapping->getName());
                    }
                }
            }
        }
    }

    private function addRouteMiddleware(RouteInterface|RouteGroupInterface $route, \ReflectionMethod $method): void
    {
        /** @var MiddlewareFactory[] $filters */
        $filters = [];
        foreach ($method->getAttributes(MiddlewareFactory::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attr = $attribute->newInstance();
            $filters[get_class($attr)] = $attr;
        }
        foreach ($method->getDeclaringClass()->getAttributes(MiddlewareFactory::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attr = $attribute->newInstance();
            if (!isset($filters[get_class($attr)])) {
                $filters[get_class($attr)] = $attr;
            }
        }
        if (!empty($filters)) {
            usort($filters, static function (MiddlewareFactory $a, MiddlewareFactory $b): int {
                return $a->getPriority() - $b->getPriority();
            });
            foreach ($filters as $filter) {
                $route->add($filter->create($this->container));
            }
        }
    }
}
