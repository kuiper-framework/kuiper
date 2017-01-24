<?php
namespace kuiper\web\router\fixtures;

use kuiper\web\annotation\route\RoutePrefix;
use kuiper\web\annotation\route\Get;

/**
 * @RoutePrefix
 */
class IndexController
{
    /**
     * @Get("[/]")
     */
    public function indexAction()
    {
    }
}
