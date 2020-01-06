<?php

namespace kuiper\web;

interface UrlResolverInterface extends RequestAwareInterface
{
    /**
     * generates url.
     *
     * @param string $name      route name
     * @param array  $arguments
     * @param bool   $absolute
     *
     * @return string
     *
     * @throws exception\RouteNotFoundException
     */
    public function get($name, array $arguments = [], $absolute = false);
}
