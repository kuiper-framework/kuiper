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
     * Adds middleware
     * 
     * @param callable $middleware
     * @param int|string $position if int, same as before:{constant}, if string, before:{id} or after:{id}
     * @param string $id
     * @return self
     */
    public function add(callable $middleware, $position = self::ROUTE, $id = null);

    /**
     * run application
     *
     * @param ServerRequestInterface $req request message
     * @param boolean $silent sending response when false
     * 
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function run(ServerRequestInterface $req = null, $silent = false);
}
