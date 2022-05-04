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

namespace kuiper\web;

use DI\Attribute\Inject;
use kuiper\web\exception\RedirectException;
use kuiper\web\security\SecurityContext;
use kuiper\web\session\FlashInterface;
use kuiper\web\session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;

/**
 * @method getResponse(): ResponseInterface
 * @method getRequest(): \Psr\Http\Message\RequestInterface
 */
trait ControllerTrait
{
    #[Inject]
    protected App $app;

    protected function getSession(): SessionInterface
    {
        return SecurityContext::fromRequest($this->getRequest())->getSession();
    }

    protected function getFlash(): FlashInterface
    {
        return SecurityContext::fromRequest($this->getRequest())->getFlash();
    }

    protected function json($data): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = $this->getResponse();
        $response->getBody()->write(json_encode($data));

        return $response->withHeader('content-type', 'application/json');
    }

    protected function redirect(string $url, int $code = 302): void
    {
        throw new RedirectException($url, $code);
    }

    protected function urlFor(string $routeName, array $data = [], array $queryParams = []): string
    {
        return $this->app->getRouteCollector()->getRouteParser()->urlFor($routeName, $data, $queryParams);
    }

    protected function fullUrlFor(string $routeName, array $data = [], array $queryParams = []): string
    {
        return $this->app->getRouteCollector()->getRouteParser()->fullUrlFor(
            $this->getRequest()->getUri(), $routeName, $data, $queryParams
        );
    }
}
