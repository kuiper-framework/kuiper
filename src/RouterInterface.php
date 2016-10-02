<?php
namespace kuiper\web;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    /**
     * Resolves route. route contains three element
     *  - route resolve result code, one of the constant value defined in RouteInterface
     *  - route callback 
     *  - route callback parameters
     * 
     * @param ServerRequestInterface $request
     * @return RouteInterface
     * @throws \kuiper\web\exception\NotFoundException
     *  \kuiper\web\exception\MethodNotAllowedException
     */
    public function dispatch(ServerRequestInterface $request);
}
