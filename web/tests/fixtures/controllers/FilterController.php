<?php
namespace kuiper\web\fixtures\controllers;

use kuiper\web\annotation\filter\Json;
use kuiper\web\annotation\filter\PostOnly;

class FilterController
{
    /**
     * @Json
     */
    public function index()
    {
    }

    /**
     * @PostOnly
     */
    public function post()
    {
    }
}
