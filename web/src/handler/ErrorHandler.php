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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;
    /**
     * @var array
     */
    private $errorRenderers;

    /**
     * @var string
     */
    private $defaultContentType;

    /**
     * @var ErrorRendererInterface|null
     */
    private $logErrorRenderer;

    /**
     * @var string
     */
    private $includeStacktraceStrategy;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        array $errorRenderers,
        ?ErrorRendererInterface $logErrorRenderer,
        ?LoggerInterface $logger,
        string $defaultContentType = MediaType::TEXT_HTML,
        string $includeStacktraceStrategy = IncludeStacktrace::NEVER)
    {
        $this->responseFactory = $responseFactory;
        $this->errorRenderers = $errorRenderers;
        $this->defaultContentType = $defaultContentType;
        $this->includeStacktraceStrategy = $includeStacktraceStrategy;
        $this->logErrorRenderer = $logErrorRenderer ?? $errorRenderers[MediaType::TEXT_PLAIN] ?? null;
        if (!isset($errorRenderers[$this->defaultContentType])) {
            throw new \InvalidArgumentException("error renderer for {$this->defaultContentType} not found");
        }
        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails): ResponseInterface
    {
        if ($logErrors && null !== $this->logErrorRenderer) {
            $this->writeToErrorLog($request, $exception);
        }

        return $this->respond($request, $exception, $displayErrorDetails);
    }

    protected function respond(ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails): ResponseInterface
    {
        $statusCode = $this->determineStatusCode($request, $exception);
        $contentType = self::determineContentType($request, array_keys($this->errorRenderers))
            ?? $this->defaultContentType;
        $response = $this->responseFactory->createResponse($statusCode)
            ->withHeader('Content-type', $contentType);

        if ($exception instanceof HttpMethodNotAllowedException) {
            $allowedMethods = implode(', ', $exception->getAllowedMethods());
            $response = $response->withHeader('Allow', $allowedMethods);
        }

        $renderer = $this->errorRenderers[$contentType] ?? $this->errorRenderers[$this->defaultContentType];
        $body = $renderer($exception, $displayErrorDetails);
        $response->getBody()->write($body);

        return $response;
    }

    /**
     * Write to the error log if $logErrors has been set to true.
     */
    protected function writeToErrorLog(ServerRequestInterface $request, Throwable $exception): void
    {
        $error = call_user_func($this->logErrorRenderer, $exception, $this->getIncludeStacktrace($request));
        $this->logger->error($error);
    }

    protected function getIncludeStacktrace(ServerRequestInterface $request): bool
    {
        switch ($this->includeStacktraceStrategy) {
            case IncludeStacktrace::ALWAYS:
                return true;
            case IncludeStacktrace::NEVER:
                return false;
            default:
                return isset($request->getQueryParams()['trace']);
        }
    }

    protected function determineStatusCode(ServerRequestInterface $request, Throwable $exception): int
    {
        if ('OPTIONS' === $request->getMethod()) {
            return 200;
        }

        if ($exception instanceof HttpException) {
            return $exception->getCode();
        }

        return 500;
    }

    /**
     * Determine which content type we know about is wanted using Accept header.
     *
     * Note: This method is a bare-bones implementation designed specifically for
     * Slim's error handling requirements. Consider a fully-feature solution such
     * as willdurand/negotiation for any other situation.
     *
     * @return string
     */
    public static function determineContentType(ServerRequestInterface $request, array $contentTypes): ?string
    {
        $acceptHeader = $request->getHeaderLine('Accept');
        if (false !== strpos($acceptHeader, ';')) {
            [$acceptHeader, $ignore] = explode(';', $acceptHeader, 2);
        }
        $selectedContentTypes = array_intersect(
            explode(',', $acceptHeader),
            $contentTypes
        );
        $count = count($selectedContentTypes);

        if ($count > 0) {
            $current = current($selectedContentTypes);

            /*
             * Ensure other supported content types take precedence over text/plain
             * when multiple content types are provided via Accept header.
             */
            if ('text/plain' === $current && $count > 1) {
                return next($selectedContentTypes);
            }

            return $current;
        }

        if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/'.$matches[1];
            if (in_array($mediaType, $contentTypes, true)) {
                return $mediaType;
            }
        }

        return null;
    }

    /**
     * Set the renderer for the error logger.
     *
     * @param ErrorRendererInterface|string|callable $logErrorRenderer
     */
    public function setLogErrorRenderer($logErrorRenderer): void
    {
        $this->logErrorRenderer = $logErrorRenderer;
    }

    public function setDefaultContentType(string $defaultContentType): void
    {
        $this->defaultContentType = $defaultContentType;
    }

    public function setIncludeStacktraceStrategy(string $includeStacktraceStrategy): void
    {
        $this->includeStacktraceStrategy = $includeStacktraceStrategy;
    }
}
