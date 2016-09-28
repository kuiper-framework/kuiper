<?php
namespace chaozhuo\web;

interface ApplicationInterface
{
    const START = 0;
    const ERROR = 10;
    const ROUTE = 20;
    const DISPATCH = 30;

    /**
     * @param callable $middleware
     * @param int|string $position if int, same as before:{constant}, if string, before:{id} or after:{id}
     * @param string $id
     */
    public function add(callable $middleware, $position = self::ROUTE, $id = null);

    /**
     * run application
     */
    public function run($silent = false);
}
