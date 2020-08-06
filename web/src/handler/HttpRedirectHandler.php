<?php

declare(strict_types=1);

namespace kuiper\web\handler;

use kuiper\web\exception\HttpRedirectException;
use kuiper\web\http\MediaType;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

class HttpRedirectHandler implements ErrorHandlerInterface
{
    /**
     * @var ErrorHandlerInterface
     */
    private $defaultErrorHandler;
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * UnauthorizedErrorHandler constructor.
     */
    public function __construct(
        ErrorHandlerInterface $defaultErrorHandler,
        ResponseFactoryInterface $responseFactory)
    {
        $this->defaultErrorHandler = $defaultErrorHandler;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails): ResponseInterface
    {
        $contentType = ErrorHandler::determineContentType($request, [
                MediaType::TEXT_HTML,
                MediaType::APPLICATION_XML,
                MediaType::APPLICATION_JSON,
            ]) ?? MediaType::TEXT_HTML;
        if (MediaType::TEXT_HTML === $contentType) {
            /* @var HttpRedirectException $exception */
            return $this->responseFactory->createResponse($exception->getCode())
                ->withHeader('Location', $exception->getUrl());
        }

        return $this->defaultErrorHandler->__invoke(
            $request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails);
    }
}
