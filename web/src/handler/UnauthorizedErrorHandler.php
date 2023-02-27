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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

class UnauthorizedErrorHandler extends AbstractErrorHandler
{
    public function __construct(
        ErrorHandlerInterface $defaultErrorHandler,
        ResponseFactoryInterface $responseFactory,
        private readonly LoginUrlBuilderInterface $loginUriBuilder)
    {
        parent::__construct($defaultErrorHandler, $responseFactory);
    }

    protected function respondHtml(ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails): ResponseInterface
    {
        return $this->getResponseFactory()->createResponse(302)
            ->withHeader('Location', $this->loginUriBuilder->build($request));
    }
}
