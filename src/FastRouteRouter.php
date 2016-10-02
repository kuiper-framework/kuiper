<?php
namespace kuiper\web;

use FastRoute\Dispatcher;
use LogicException;

class FastRouteRouter implements RouterInterface
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var string
     */
    private $defaultNamespace;
    
    public function __construct(Dispatcher $dispatcher, $namespace = null)
    {
        if (isset($namespace)) {
            $this->defaultNamespace = rtrim($namespace, "\\") . "\\";
        }
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
        $routeInfo[0] = self::$CODES_MAP[$routeInfo[0]];
        if ($routeInfo[0] === Dispatcher::FOUND) {
            if (is_string($routeInfo[1])
                && strpos($routeInfo[1], '@') !== false) {
            }
            list($controller, $action) = explode('@', $routeInfo[1]);
            if (isset($this->defaultNamespace) && $controller[0] !== '\\') {
                $controller = $this->defaultNamespace . $controller;
            }
            if (empty($action) && isset($routeInfo[2]['action'])) {
                $action = $routeInfo[2]['action'];
            }
            $routeInfo[1] = [$controller, $action];
            return $routeInfo;
        } elseif ($routeInfo[0] === RouterInterface::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowedException($routeInfo[1], $request, $response);
        } else {
            throw new NotFoundException($request, $response);
        }
    }
}
