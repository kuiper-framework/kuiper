<?php

namespace kuiper\web;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    /**
     * Resolves route.
     *
     * @param ServerRequestInterface $request
     *
     * @return RouteInterface
     *
     * @throws \kuiper\web\exception\NotFoundException
     * @throws \kuiper\web\exception\MethodNotAllowedException
     */
    public function dispatch(ServerRequestInterface $request);
}
