<?php

namespace kuiper\web\middlewares;

use kuiper\annotations\ReaderInterface;
use kuiper\web\annotation\filter\FilterInterface;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;

class Filter
{
    /**
     * @var ReaderInterface
     */
    private $annotationReader;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $cache;

    public function __construct(ReaderInterface $annotationReader, ContainerInterface $container)
    {
        $this->annotationReader = $annotationReader;
        $this->container = $container;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $route = $request->getAttribute('route');
        if ($route === null) {
            throw new LogicException('Please add Filter middleware before dispatch');
        }
        $callback = $route->getAction();
        if (is_array($callback)) {
            $filters = $this->getFilters($callback[0], $callback[1]);

            return $this->callFilters($request, $response, $filters, $next);
        } else {
            return $next($request, $response);
        }
    }

    private function getFilters($controller, $action)
    {
        $className = is_string($controller) ? $controller : get_class($controller);
        if (isset($this->cache[$className][$action])) {
            return $this->cache[$className][$action];
        }
        $class = new ReflectionClass($className);
        $method = $class->getMethod($action);

        $annotations = [];
        foreach ($this->annotationReader->getClassAnnotations($class) as $annotation) {
            if ($annotation instanceof FilterInterface) {
                $annotations[get_class($annotation)] = $annotation;
            }
        }
        foreach ($this->annotationReader->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof FilterInterface) {
                $annotations[get_class($annotation)] = $annotation;
            }
        }
        usort($annotations, function ($a, $b) {
            return $a->getPriority() - $b->getPriority();
        });
        $filters = [];
        foreach ($annotations as $annotation) {
            $filters[] = $annotation->createMiddleware($this->container);
        }

        return $this->cache[$className][$action] = $filters;
    }

    private function callFilters($request, $response, $filters, $final, $index = 0)
    {
        if ($index < count($filters)) {
            return $filters[$index]($request, $response, function ($request, $response) use ($filters, $final, $index) {
                return $this->callFilters($request, $response, $filters, $final, $index + 1);
            });
        } else {
            return $final($request, $response);
        }
    }
}
