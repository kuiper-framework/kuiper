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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractController implements ControllerInterface
{
    protected ?ServerRequestInterface $request = null;

    /**
     * A response object to send to the HTTP client.
     */
    protected ?ResponseInterface $response = null;

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function withRequest(ServerRequestInterface $request): static
    {
        $new = clone $this;
        $new->request = $request;

        return $new;
    }

    /**
     * Gets request.
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function withResponse(ResponseInterface $response): static
    {
        $new = clone $this;
        $new->response = $response;

        return $new;
    }

    /**
     * Gets response.
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
