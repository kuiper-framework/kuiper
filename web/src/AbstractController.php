<?php

declare(strict_types=1);

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractController implements ControllerInterface
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * A response object to send to the HTTP client.
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function withRequest(ServerRequestInterface $request)
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
    public function withResponse(ResponseInterface $response)
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
