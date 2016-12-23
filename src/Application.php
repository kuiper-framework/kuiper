<?php
namespace kuiper\web;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use kuiper\web\exception\NotFoundException;
use kuiper\web\exception\MethodNotAllowedException;
use kuiper\web\exception\HttpException;
use kuiper\web\exception\DispatchException;
use kuiper\web\ControllerInterface;
use RuntimeException;
use LogicException;
use Exception;
use Throwable;
use Closure;

class Application implements ApplicationInterface
{
    
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @var array
     */
    private $middlewareQueue;

    /**
     * @var ErrorHandlerInterface
     */
    private $errorHandler;

    /**
     * @var array
     */
    private static $STAGES = ['START', 'ERROR', 'ROUTE', 'DISPATCH'];

    /**
     * Avaliable options:
     *  - chuck_size response chuck size
     * 
     * @param ContainerInterface $container
     * @param array $options 
     */
    public function __construct(ContainerInterface $container, array $options = [])
    {
        $this->container = $container;
        $this->options = $options;
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
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function run(ServerRequestInterface $req = null, $silent = false)
    {
        if ($this->middlewareQueue === null) {
            $this->buildMiddlewareQueue();
        }
        $request = $req ?: $this->getRequest();
        $response = $this->getResponse();
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
        if (isset($this->options['chuck_size'])) {
            $chunkSize = $this->options['chuck_size'];
            if (!is_int($chunkSize) || $chunkSize < 1) {
                throw new RuntimeException("chuck_size should be an positive integer");
            }
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
            $middleware = $this->middlewareQueue[$i];
            if ($middleware instanceof Closure) {
                $middleware->bindTo($this->getContainer());
            }
            return $middleware($request, $response, function ($request, $response) use ($i) {
                return $this->callMiddlewareQueue($request, $response, $i+1);
            });
        } else {
            return $response;
        }
    }

    /**
     * The middleware to resolve route info
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @throws MethodNotAllowedException
     *       NotFoundException
     */
    protected function resolveRoute(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $route = $this->getRouter()->dispatch($request, $response);
        if (!($route instanceof RouteInterface)) {
            throw new LogicException("RouterInterface::dispatch should return RouteInterface, got " . gettype($route));
        }
        return $next($request->withAttribute('route', $route), $response);
    }

    /**
     * The middleware to execute route callback
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @throws DispatchException
     */
    protected function dispatch(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            $route = $request->getAttribute('route');
            if (method_exists($route, 'setContainer')) {
                $route->setContainer($this->container);
            }
            return $route->run($request, $response);
        } catch (Exception $e) {
            throw new DispatchException($request, $response, null, $e);
        }
    }

    /**
     * The middleware to handle exception
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @throws DispatchException
     */
    protected function handleError(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            return $next($request, $response);
        } catch (Exception $e) {
            return $this->handleException($e, $request, $response);
        } catch (Throwable $e) {
            return $this->handleException($e, $request, $response);
        }
    }

    protected function handleException($e, ServerRequestInterface $request, ResponseInterface $response)
    {
        $handler = $this->getErrorHandler();
        if ($e instanceof HttpException) {
            $handler->setRequest($e->getRequest());
            $handler->setResponse($e->getResponse());
        } else {
            $handler->setRequest($request);
            $handler->setResponse($response);
        }
        return $handler->handle($e instanceof DispatchException ? $e->getPrevious() : $e);
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

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getRouter()
    {
        return $this->container->get(RouterInterface::class);
    }

    protected function getErrorHandler()
    {
        if ($this->errorHandler === null) {
            if ($this->container->has(ErrorHandlerInterface::class)) {
                $this->errorHandler = $this->container->get(ErrorHandlerInterface::class);
            } else {
                $this->errorHandler = new DefaultErrorHandler();
            }
        }
        return $this->errorHandler;
    }

    protected function getRequest()
    {
        return $this->container->get(ServerRequestInterface::class);
    }

    protected function getResponse()
    {
        return $this->container->get(ResponseInterface::class);
    }
}
