<?php
namespace kuiper\web\middlewares;

class DeleteOnly extends RequestMethod
{
    public function __construct()
    {
        parent::__construct(['DELETE']);
    }
}
