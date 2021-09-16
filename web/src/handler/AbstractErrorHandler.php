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

namespace kuiper\web\handler;

use kuiper\web\http\MediaType;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;

abstract class AbstractErrorHandler implements ErrorHandlerInterface
{
    /**
     * @var ErrorHandlerInterface
     */
    protected $defaultErrorHandler;
    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    public function __construct(ErrorHandlerInterface $defaultErrorHandler, ResponseFactoryInterface $responseFactory)
    {
        $this->defaultErrorHandler = $defaultErrorHandler;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(
        ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails): ResponseInterface
    {
        $contentType = ErrorHandler::determineContentType($request, [
                MediaType::TEXT_HTML,
                MediaType::APPLICATION_XML,
                MediaType::APPLICATION_JSON,
            ]) ?? MediaType::TEXT_HTML;

        if (MediaType::TEXT_HTML === $contentType) {
            return $this->respondHtml($request, $exception, $displayErrorDetails);
        } else {
            return $this->respondApi($request, $exception, $logErrors);
        }
    }

    protected function respondApi(ServerRequestInterface $request, \Throwable $exception, bool $logErrors): ResponseInterface
    {
        return $this->defaultErrorHandler->__invoke(
            $request, $exception, false, $logErrors, true);
    }

    abstract protected function respondHtml(ServerRequestInterface $request, \Throwable $exception, bool $displayErrorDetails): ResponseInterface;
}
