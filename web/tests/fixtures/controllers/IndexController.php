<?php

declare(strict_types=1);

namespace kuiper\web\fixtures\controllers;

use kuiper\di\annotation\Controller;
use kuiper\web\AbstractController;
use kuiper\web\annotation\filter\LoginOnly;
use kuiper\web\annotation\GetMapping;

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
}
