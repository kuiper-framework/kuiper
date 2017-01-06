<?php

namespace kuiper\web;

interface RouteSourceInterface
{
    /**
     * @return RouteInterface[]
     */
    public function getRoutes();
}
