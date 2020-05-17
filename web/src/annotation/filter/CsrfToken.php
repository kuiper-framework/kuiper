<?php

declare(strict_types=1);

namespace kuiper\web\annotation\filter;

use kuiper\web\exception\HttpCsrfTokenException;
use kuiper\web\security\SecurityContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpMethodNotAllowedException;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class CsrfToken extends AbstractFilter
{
    /**
     * @var array all allowed http request methods
     */
    public const ALLOWED_METHODS = ['PUT', 'POST', 'DELETE'];

    /**
     * @var bool
     */
    public $repeatOk = true;

    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container): MiddlewareInterface
    {
        return new class($this->repeatOk) implements MiddlewareInterface {
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
                if (!in_array($request->getMethod(), CsrfToken::ALLOWED_METHODS, true)) {
                    throw (new HttpMethodNotAllowedException($request))->setAllowedMethods(CsrfToken::ALLOWED_METHODS);
                }
                $csrfToken = SecurityContext::fromRequest($request)->getCsrfToken();
                if ($csrfToken->check($request, $destroy = !$this->repeatOk)) {
                    return $handler->handle($request);
                }

                throw new HttpCsrfTokenException($request);
            }
        };
    }
}
