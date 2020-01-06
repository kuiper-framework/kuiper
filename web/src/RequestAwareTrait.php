<?php

namespace kuiper\web;

use Psr\Http\Message\ServerRequestInterface;

trait RequestAwareTrait
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Sets request.
     *
     * @param ServerRequestInterface $request
     *
     * @return $this
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Gets request.
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
}
