<?php

namespace kuiper\web;

use Psr\Http\Message\ServerRequestInterface;

interface ApplicationInterface
{
    const START = 0;
    const ERROR = 10;
    const ROUTE = 20;
    const DISPATCH = 30;

    /**
     * Adds middleware.
     *
     * Position can be either the constants defined in ApplicationInterface or
     * an string like 'before:{middleware_id}' or 'after:{middleware_id}'
     *
     * @param callable   $middleware
     * @param int|string $position
     * @param string     $id
     *
     * @return self
     */
    public function add(callable $middleware, $position = self::ROUTE, $id = null);

    /**
     * Run application.
     *
     * @param ServerRequestInterface $request request message
     * @param bool                   $silent  won't sending response when true
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function run(ServerRequestInterface $request = null, $silent = false);
}
