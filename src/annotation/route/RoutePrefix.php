<?php
namespace kuiper\web\annotation\route;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class RoutePrefix
{
    /**
     * @var string
     */
    public $value;
}
