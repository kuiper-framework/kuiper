<?php

namespace kuiper\web;

use Psr\Http\Message\ServerRequestInterface;

interface RequestAwareInterface
{
    /**
     * Sets request.
     *
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request);
}
