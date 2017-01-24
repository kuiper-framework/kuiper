<?php
namespace kuiper\web\middlewares\fixtures;

use kuiper\web\annotation\filter\Json;
use kuiper\web\annotation\filter\PostOnly;

class IndexController
{
    /**
     * @Json
     */
    public function indexAction()
    {
    }

    /**
     * @PostOnly
     */
    public function postAction()
    {
    }
}