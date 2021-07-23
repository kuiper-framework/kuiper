<?php

declare(strict_types=1);

namespace kuiper\web\middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HealthyStatus implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;
    /**
     * @var string[]
     */
    private $pathList;
    /**
     * @var string
     */
    private $body;

    /**
     * HealthyStatus constructor.
     *
     * @param string|array $path
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        $path = '/status.html',
        string $body = 'ok')
    {
        $this->pathList = (array) $path;
        $this->responseFactory = $responseFactory;
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array($request->getUri()->getPath(), $this->pathList, true)) {
            return $this->createHealthyStatusResponse($request);
        }

        return $handler->handle($request);
    }

    protected function createHealthyStatusResponse(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        if ('HEAD' === $request->getMethod()) {
            return $response;
        }
        $response->getBody()->write($this->body);

        return $response;
    }
}
