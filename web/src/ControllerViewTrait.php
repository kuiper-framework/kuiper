<?php

declare(strict_types=1);

namespace kuiper\web;

use DI\Annotation\Inject;
use kuiper\web\view\ViewInterface;
use Psr\Http\Message\ResponseInterface;

trait ControllerViewTrait
{
    /**
     * @Inject()
     *
     * @var ViewInterface
     */
    protected $view;

    protected function render(string $page, array $context = []): ResponseInterface
    {
        $response = $this->getResponse();
        $response->getBody()->write(
            $this->view->render($page, $context + $this->getDefaultContext()));

        return $response;
    }

    protected function renderAsString(string $page, array $context = []): string
    {
        return $this->view->render($page, $context + $this->getDefaultContext());
    }

    protected function getDefaultContext(): array
    {
        return [];
    }
}
