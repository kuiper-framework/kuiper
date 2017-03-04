<?php

namespace kuiper\web\annotation\route;

/**
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
class Route
{
    /**
     * @var string Path pattern
     * @Default
     */
    public $value;

    /**
     * @var array request methods
     */
    public $methods;

    /**
     * @var string route name
     */
    public $name;

    /**
     * @var int
     */
    public $priority = 1024;
}
