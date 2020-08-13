<?php

declare(strict_types=1);

namespace kuiper\web\handler;

use kuiper\web\exception\RedirectException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HttpRedirectHandler extends AbstractErrorHandler
{
    /**
     * {@inheritdoc}
     */
    protected function respondHtml(ServerRequestInterface $request, \Throwable $exception, bool $displayErrorDetails): ResponseInterface
    {
        if (!$exception instanceof RedirectException) {
            return $this->defaultErrorHandler->__invoke($request, $exception, $displayErrorDetails, true, true);
        }
        /* @var RedirectException $exception */
        $code = $exception->getCode();
        if ($code < 300 || $code > 310) {
            $code = 302;
        }

        return $this->responseFactory->createResponse($code)
            ->withHeader('Location', $exception->getUrl());
    }
}
