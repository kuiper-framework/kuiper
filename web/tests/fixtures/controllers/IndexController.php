<?php

declare(strict_types=1);

namespace kuiper\web\fixtures\controllers;

use kuiper\di\annotation\Controller;
use kuiper\web\AbstractController;
use kuiper\web\annotation\GetMapping;
use kuiper\web\annotation\LoginOnly;
use kuiper\web\annotation\RequestMapping;

/**
 * @Controller()
 */
class IndexController extends AbstractController
{
    /**
     * @GetMapping("/")
     * @LoginOnly()
     */
    public function home(): void
    {
        $this->response->getBody()->write("hello\n");
    }

    /**
     * @RequestMapping("/index")
     */
    public function index(): void
    {
        $this->response->getBody()->write(__METHOD__);
    }
}
