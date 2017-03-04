<?php

namespace kuiper\web\annotation\route;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Get extends Route
{
    /**
     * @var array request methods
     */
    public $methods = ['GET'];
}
