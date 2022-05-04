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

    public function __construct(private readonly bool $repeatOk)
    {
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
        if ($csrfToken->check($request, !$this->repeatOk)) {
            return $handler->handle($request);
        }

        throw new HttpCsrfTokenException($request);
    }
}
