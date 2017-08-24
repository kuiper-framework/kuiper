<?php

namespace app\controllers;

use kuiper\web\Controller;

class IndexController extends Controller
{
    public function hello($name)
    {
        $this->response->getBody()->write("Hello, $name");
    }
}
