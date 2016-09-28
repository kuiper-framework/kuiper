<?php
namespace chaozhuo\web;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use chaozhuo\web\exception\NotFoundException;
use chaozhuo\web\exception\MethodNotAllowedException;
use chaozhuo\web\exception\HttpException;
use chaozhuo\web\exception\DispatchException;
use RuntimeException;
use Exception;
use Throwable;

class Application implements ApplicationInterface
{
    
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @var array
     */
    private $middlewareQueue;

    /**
     * @var array
     */
    private static $STAGES = ['START', 'ERROR', 'ROUTE', 'DISPATCH'];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function add(callable $middleware, $position = self::ROUTE, $id = null)
    {
        if (is_int($position)) {
            if (!in_array($position, [self::START, self::ERROR, self::ROUTE, self::DISPATCH])) {
                throw new InvalidArgumentException("Invalid position '{$position}'");
            }
            $this->middlewares[$position][] = [$id, $middleware];
        } elseif (is_string($position)) {
            if (strpos($position, 'before:') === 0) {
                $before = true;
                $position = substr($position, 7 /*strlen('before:')*/);
            } elseif (strpos($position, 'after:') === 0) {
                $before = false;
                $position = substr($position, 6 /*strlen('after:')*/);
            } else {
                throw new InvalidArgumentException("Invalid position '{$position}', expects 'before:ID' or 'after:ID'");
            }
            if (($key = array_search(strtoupper($position), self::$STAGES)) !== false) {
                if ($key === count(self::$STAGES)-1 && !$before) {
                    throw new InvalidArgumentException("Cannot add middleware after dispatch");
                }
                $position = constant(__CLASS__.'::'.self::$STAGES[$before ? $key : $key+1]);
                $this->middlewares[$position][] = [$id, $middleware];
            } else {
                $found = false;
                foreach ($this->middlewares as $stage => &$stageMiddlewares) {
                    foreach ($stageMiddlewares as $i => $scope) {
                        if ($position === $scope[0]) {
                            array_splice($stageMiddlewares, ($before ? $i : $i+1), 0, [[$id, $middleware]]);
                            $found = true;
                            break 2;
                        }
                    }
                }
                if (!$found) {
                    throw new InvalidArgumentException("Middleware '{$position}' was not registered");
                }
            }
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function run($silent = false)
    {
        if ($this->middlewareQueue === null) {
            $this->buildMiddlewareQueue();
        }
        $request = $this->container->get(ServerRequestInterface::class);
        $response = $this->container->get(ResponseInterface::class);
        $response = $this->callMiddlewareQueue($request, $response);
        if (!$silent) {
            $this->respond($response);
        }
        return $response;
    }

    /**
     * Send the response the client
     *
     * @param ResponseInterface $response
     */
    protected function respond(ResponseInterface $response)
    {
        if ($this->container->has('config.response.chuck_size')) {
            $chunkSize = $this->container->get('config.response.chuck_size');
        } else {
            $chunkSize = 4096;
        }
        $this->send($response, $chunkSize);
    }
    
    protected function getKernelMiddlewares()
    {
        return [
            self::ERROR => [$this, 'handleError'],
            self::ROUTE => [$this, 'resolveRoute'],
            self::DISPATCH => [$this, 'dispatch']
        ];
    }

    protected function buildMiddlewareQueue()
    {
        $middlewares = [];
        $kernel = $this->getKernelMiddlewares();
        foreach ([self::START, self::ERROR, self::ROUTE, self::DISPATCH] as $stage) {
            if (isset($this->middlewares[$stage])) {
                foreach ($this->middlewares[$stage] as $scope) {
                    $middlewares[] = $scope[1];
                }
            }
            if (isset($kernel[$stage])) {
                $middlewares[] = $kernel[$stage];
            }
        }
        $this->middlewareQueue = $middlewares;
    }

    protected function callMiddlewareQueue(ServerRequestInterface $request, ResponseInterface $response, $i = 0)
    {
        if ($i < count($this->middlewareQueue)) {
            return $this->middlewareQueue[$i]($request, $response, function ($request, $response) use ($i) {
                return $this->callMiddlewareQueue($request, $response, $i+1);
            });
        } else {
            return $response;
        }
    }
    
    protected function resolveRoute(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $router = $this->container->get(RouterInterface::class);
        $routeInfo = $router->dispatch($request);
        if ($routeInfo[0] === RouterInterface::FOUND) {
            $route = [
                'callback' => $routeInfo[1],
                'params' => $routeInfo[2]
            ];
            if (is_array($route['callback'])) {
                if (count($route['callback']) != 2) {
                    throw new RuntimeException("Invalid route " . json_encode($route['callback']));
                }
                list ($controller, $action) = $route['callback'];
                if (!method_exists($controller, $action)) {
                    throw new RuntimeException("Invalid route " . json_encode($route['callback']));
                }
                $route['controller'] = $controller;
                $route['action'] = $action;
            }
            return $next($request->withAttribute('routeInfo', $route), $response);
        } elseif ($routeInfo[0] === RouterInterface::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowedException($routeInfo[1], $request, $response);
        } else {
            throw new NotFoundException($request, $response);
        }
        
        return $next($request->withAttribute('routeInfo', $routeInfo), $response);
    }

    protected function dispatch(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            return $this->dispatchRoute($request, $response, $request->getAttribute('routeInfo'));
        } catch (Exception $e) {
            throw new DispatchException($request, $response, null, $e);
        }
    }

    protected function dispatchRoute($request, $response, $routeInfo)
    {
        $callback = $routeInfo['callback'];
        if (isset($routeInfo['controller'])) {
            $controller = $routeInfo['controller'];
            if (is_string($controller)) {
                $controller = $this->container->get($controller);
            }
            if ($controller instanceof ControllerInterface) {
                $controller->setRequest($request);
                $controller->setResponse($response);
                $controller->initialize();
                $callback[0] = $controller;
                $result = call_user_func_array($callback, $routeInfo['params']);
                if ($result === null) {
                    return $controller->getResponse();
                } else {
                    return $result;
                }
            } else {
                $callback = [$controller, $action];
            }
        }

        if (!is_callable($callback, true)) {
            throw new RuntimeException("Invalid route " . json_encode($callback));
        }
        return call_user_func($callback, $request, $response, $routeInfo['params']);
    }

    protected function handleError(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            return $next($request, $response);
        } catch (Exception $e) {
            return $this->handleException($e, $request, $response);
        } catch (Throwable $e) {
            return $this->handleException($e, $response, $response);
        }
    }

    protected function handleException($e, ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->container->has(ErrorHandlerInterface::class)) {
            $handler = $this->container->get(ErrorHandlerInterface::class);
            if ($e instanceof HttpException) {
                $handler->setRequest($e->getRequest());
                $handler->setResponse($e->getResponse());
            } else {
                $handler->setRequest($request);
                $handler->setResponse($response);
            }
            return $handler->handle($e instanceof DispatchException ? $e->getPrevious() : $e);
        }
        // default error handler
        throw $e;
    }

    /**
     * Helper method, which returns true if the provided response must not output a body and false
     * if the response could have a body.
     *
     * see https://tools.ietf.org/html/rfc7231
     *
     * @param ResponseInterface $response
     * @return bool
     */
    protected function isEmptyResponse(ResponseInterface $response)
    {
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }

        return in_array($response->getStatusCode(), [204, 205, 304]);
    }

    protected function send($response, $chunkSize = 4096)
    {
        // Send response
        if (!headers_sent()) {
            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // Body
        if (!$this->isEmptyResponse($response)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $contentLength  = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }

            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;
                    
                    $amountToRead -= strlen($data);
                                        
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
    }
}
