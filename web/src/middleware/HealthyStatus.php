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

namespace kuiper\web\middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HealthyStatus implements MiddlewareInterface
{
    private readonly array $pathList;

    /**
     * HealthyStatus constructor.
     *
     * @param string|string[] $path
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        string|array $path = '/status.html',
        private readonly string $body = 'ok')
    {
        $this->pathList = (array) $path;
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
