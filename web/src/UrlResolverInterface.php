<?php
namespace kuiper\web;

interface UrlResolverInterface
{
    /**
     * generates url
     *
     * @param string $name route name
     * @param array $arguments
     * @return string
     */
    public function get($name, array $arguments);
}
