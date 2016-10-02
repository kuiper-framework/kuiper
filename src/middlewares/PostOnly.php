<?php
namespace kuiper\web\middlewares;

class PostOnly extends RequestMethod
{
    public function __construct()
    {
        parent::__construct(['Post']);
    }
}
