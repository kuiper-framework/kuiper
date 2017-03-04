<?php

namespace kuiper\web\annotation\route;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Post extends Route
{
    /**
     * @var array request methods
     */
    public $methods = ['POST'];
}
