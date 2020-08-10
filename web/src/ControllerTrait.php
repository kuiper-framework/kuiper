<?php

declare(strict_types=1);

namespace kuiper\web;

use DI\Annotation\Inject;
use kuiper\web\exception\RedirectException;
use kuiper\web\security\SecurityContext;
use kuiper\web\session\FlashInterface;
use kuiper\web\session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;

trait ControllerTrait
{
    /**
     * @Inject()
     *
     * @var App
     */
    protected $app;

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
