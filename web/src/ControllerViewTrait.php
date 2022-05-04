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
use kuiper\web\view\ViewInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @method getResponse(): ResponseInterface
 */
trait ControllerViewTrait
{
    #[Inject]
    protected ViewInterface $view;

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
