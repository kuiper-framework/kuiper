<?php
namespace kuiper\web\annotation\route;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Put extends Route
{
    /**
     * @var array
     */
    public $methods = ['PUT'];
}
