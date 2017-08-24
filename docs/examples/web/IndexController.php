<?php

use kuiper\web\Controller;

class IndexController extends Controller
{
    public function hello($name)
    {
        $this->response->getBody()->write("Hello, $name");
    }
}
