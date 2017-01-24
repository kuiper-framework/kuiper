<?php
namespace kuiper\web\middlewares;

class PutOnly extends RequestMethod
{
    public function __construct()
    {
        parent::__construct(['PUT']);
    }
}
