<?php

namespace kuiper\web\annotation\route;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Delete extends Route
{
    /**
     * @var array request methods
     */
    public $methods = ['DELETE'];
}
