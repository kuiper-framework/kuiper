<?php

namespace kuiper\web;

use kuiper\web\exception\HttpException;
use kuiper\web\exception\MethodNotAllowedException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

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
    private $middlewareStack;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var array
     */
    private static $STAGES = [
        'START',
        'ERROR',
        'ROUTE',
        'DISPATCH',
    ];

    /**
     * Available options:
     *  - chuck_size response chuck size.
     *
     * @param ContainerInterface $container
     * @param array              $options
     */
    public function __construct(ContainerInterface $container, array $options = [])
    {
        $this->container = $container;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function add(callable $middleware, $position = self::ROUTE, string $id = null)
    {
        if (is_int($position)) {
            return $this->addMiddleware($position, $id, $middleware);
        }
        if (!preg_match('/^(before|after):(.*)/', $position, $matches)) {
            throw new \InvalidArgumentException("Invalid position, expects 'before:{ID}' or 'after:{ID}', got '{$position}'");
        }
        $isBefore = $matches[1] == 'before';
        $position = $matches[2];

        if (($key = array_search(strtoupper($position), self::$STAGES)) !== false) {
            if ($key === count(self::$STAGES) - 1 && !$isBefore) {
                throw new \InvalidArgumentException('Cannot add middleware after dispatch stage');
            }
            $position = constant(__CLASS__.'::'.self::$STAGES[$isBefore ? $key : $key + 1]);

            return $this->addMiddleware($position, $id, $middleware);
        }

        $found = false;
        foreach ($this->middlewares as $stage => &$stageMiddlewares) {
            foreach ($stageMiddlewares as $i => $scope) {
                if ($position === $scope[0]) {
                    array_splice($stageMiddlewares, ($isBefore ? $i : $i + 1), 0, [[$id, $middleware]]);
                    $found = true;
                    break 2;
                }
            }
        }
        if (!$found) {
            throw new \InvalidArgumentException("Middleware '{$position}' was not registered");
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run(ServerRequestInterface $request = null, $silent = false)
    {
        if ($this->middlewareStack === null) {
            $this->buildMiddlewareStack();
        }
        $request = $request ?: $this->getRequest();
        $eventDispatcher = $this->getEventDispatcher();
        if ($eventDispatcher) {
            $eventDispatcher->dispatch(Events::BEGIN_REQUEST, $event = new GenericEvent($request));
        }
        $response = $this->callMiddlewareStack($request, $this->getResponse());
        if ($eventDispatcher) {
            $event['response'] = $response;
            $eventDispatcher->dispatch(Events::END_REQUEST, $event);
            $response = $event['response'];
        }

        return $silent ? $response : $this->respond($response);
    }

    protected function addMiddleware($position, $id, callable $middleware)
    {
        if (!in_array($position, [self::START, self::ERROR, self::ROUTE, self::DISPATCH])) {
            throw new \InvalidArgumentException("Invalid position '{$position}'");
        }
        if ($middleware instanceof \Closure) {
            $middleware->bindTo($this->getContainer());
        }
        $this->middlewares[$position][] = [$id, $middleware];

        return $this;
    }

    protected function getKernelMiddlewares()
    {
        return [
            self::ERROR => [$this, 'handleError'],
            self::ROUTE => [$this, 'resolveRoute'],
            self::DISPATCH => [$this, 'dispatch'],
        ];
    }

    protected function buildMiddlewareStack()
    {
        $stack = [];
        $kernel = $this->getKernelMiddlewares();
        foreach ([self::START, self::ERROR, self::ROUTE, self::DISPATCH] as $stage) {
            if (isset($this->middlewares[$stage])) {
                foreach ($this->middlewares[$stage] as $scope) {
                    $stack[] = $scope[1];
                }
            }
            if (isset($kernel[$stage])) {
                $stack[] = $kernel[$stage];
            }
        }
        $this->middlewareStack = $stack;
    }

    protected function callMiddlewareStack(ServerRequestInterface $request, ResponseInterface $response, $index = 0)
    {
        if ($index < count($this->middlewareStack)) {
            $middleware = $this->middlewareStack[$index];
            $this->request = $request;
            $this->response = $response;

            return $middleware($request, $response, function ($request, $response) use ($index) {
                return $this->callMiddlewareStack($request, $response, $index + 1);
            });
        } else {
            return $response;
        }
    }

    /**
     * Send the response the client.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function respond(ResponseInterface $response)
    {
        if (isset($this->options['chuck_size'])) {
            $chunkSize = $this->options['chuck_size'];
            if (!is_int($chunkSize) || $chunkSize < 1) {
                throw new \RuntimeException('chuck_size should be an positive integer');
            }
        } else {
            $chunkSize = 4096;
        }
        $this->send($response, $chunkSize);

        return $response;
    }

    /**
     * The middleware to handle exception.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    protected function handleError(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            return $next($request, $response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * The middleware to resolve route.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @throws MethodNotAllowedException
     *                                   NotFoundException
     */
    protected function resolveRoute(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $route = $this->getRouter()->dispatch($request, $response);
        if (!($route instanceof RouteInterface)) {
            throw new \BadMethodCallException(RouterInterface::class.'::dispatch should return RouteInterface, got '.gettype($route));
        }

        return $next($request->withAttribute('route', $route), $response);
    }

    /**
     * The middleware to execute route callback.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return
     */
    protected function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $route = $request->getAttribute('route');
        if (method_exists($route, 'setContainer')) {
            $route->setContainer($this->container);
        }

        return $route->run($request, $response);
    }

    /**
     * @param $exception
     *
     * @return ResponseInterface
     */
    protected function handleException($exception)
    {
        $handler = $this->getErrorHandler();
        $handler->setRequest($this->request);
        if ($exception instanceof HttpException) {
            if (!$exception->getResponse()) {
                $exception->setResponse($this->response);
            }

            $handler->setResponse($exception->getResponse());
        } else {
            $handler->setResponse($this->response->withStatus(500));
        }

        return $handler->handle($exception);
    }

    /**
     * Helper method, which returns true if the provided response must not output a body and false
     * if the response could have a body.
     *
     * see https://tools.ietf.org/html/rfc7231
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    protected function isEmptyResponse(ResponseInterface $response)
    {
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }

        return in_array($response->getStatusCode(), [204, 205, 304]);
    }

    protected function send(ResponseInterface $response, $chunkSize = 4096)
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
            $contentLength = $response->getHeaderLine('Content-Length');
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
        if ($this->container->has(RouteInterface::class)) {
            throw new \RuntimeException(RouterInterface::class.' is not defined in container');
        }

        return $this->container->get(RouterInterface::class);
    }

    protected function getErrorHandler()
    {
        if ($this->container->has(ErrorHandlerInterface::class)) {
            return $this->container->get(ErrorHandlerInterface::class);
        } else {
            $errorHandler = new ErrorHandler();
            if ($this->container->has(LoggerInterface::class)) {
                $errorHandler->setLogger($this->container->get(LoggerInterface::class));
            }

            return $errorHandler;
        }
    }

    protected function getRequest()
    {
        if ($this->container->has(ServerRequestInterface::class)) {
            throw new \RuntimeException(ServerRequestInterface::class.' is not defined in container');
        }

        return $this->container->get(ServerRequestInterface::class);
    }

    protected function getResponse()
    {
        if ($this->container->has(ResponseInterface::class)) {
            throw new \RuntimeException(ResponseInterface::class.' is not defined in container');
        }

        return $this->container->get(ResponseInterface::class);
    }

    protected function getEventDispatcher()
    {
        if ($this->container->has(EventDispatcherInterface::class)) {
            return $this->container->get(EventDispatcherInterface::class);
        } else {
            return false;
        }
    }
}
