<?php

declare(strict_types=1);

namespace kuiper\web;

use kuiper\swoole\http\ServerRequestHolder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;

class ControllerInvocationStrategy implements RequestHandlerInvocationStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(callable $callable, ServerRequestInterface $request, ResponseInterface $response, array $routeArguments): ResponseInterface
    {
        ServerRequestHolder::setRequest($request);
        if (is_array($callable) && $callable[0] instanceof ControllerInterface) {
            $controller = $callable[0];
            $controller = $controller->withRequest($request)
                ->withResponse($response);
            $initResult = $controller->initialize();
            if (isset($initResult)) {
                if ($initResult instanceof ResponseInterface) {
                    return $initResult;
                }

                throw new \BadMethodCallException(get_class($controller).'::initialize should return '.ResponseInterface::class.', got '.gettype($initResult));
            }
            $result = call_user_func_array([$controller, $callable[1]], array_values($routeArguments));
            if (!isset($result)) {
                return $controller->getResponse();
            }
            if ($result instanceof ResponseInterface) {
                return $result;
            }

            throw new \BadMethodCallException(get_class($controller).'::'.$callable[1].' should return null or '.ResponseInterface::class.', got '.gettype($initResult));
        }

        return $callable($request, $response, ...array_values($routeArguments));
    }
}
