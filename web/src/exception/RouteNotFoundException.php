<?php

namespace kuiper\web\exception;

class RouteNotFoundException extends \LogicException
{
    public function __construct($name)
    {
        parent::__construct("Route does not exist for name '{$name}'");
    }
}
