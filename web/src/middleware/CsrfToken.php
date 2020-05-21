<?php

declare(strict_types=1);

namespace kuiper\web\middleware;

use kuiper\web\exception\HttpCsrfTokenException;
use kuiper\web\security\SecurityContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpMethodNotAllowedException;

class CsrfToken implements MiddlewareInterface
{
    /**
     * @var array all allowed http request methods
     */
    public const ALLOWED_METHODS = ['PUT', 'POST', 'DELETE'];

    /**
     * @var bool
     */
    private $repeatOk;

    public function __construct(bool $repeatOk)
    {
        $this->repeatOk = $repeatOk;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array($request->getMethod(), self::ALLOWED_METHODS, true)) {
            throw (new HttpMethodNotAllowedException($request))->setAllowedMethods(self::ALLOWED_METHODS);
        }
        $csrfToken = SecurityContext::fromRequest($request)->getCsrfToken();
        if ($csrfToken->check($request, $destroy = !$this->repeatOk)) {
            return $handler->handle($request);
        }

        throw new HttpCsrfTokenException($request);
    }
}
