<?php

declare(strict_types=1);

namespace kuiper\web\fixtures\controllers;

use kuiper\di\annotation\Controller;
use kuiper\web\AbstractController;
use kuiper\web\annotation\filter\PreAuthorize;
use kuiper\web\annotation\GetMapping;
use kuiper\web\annotation\RequestMapping;

/**
 * @Controller()
 * @RequestMapping("/auth")
 */
class TestAuthController extends AbstractController
{
    /**
     * @GetMapping("/home")
     * @PreAuthorize({"book:view", "book:edit"})
     */
    public function home(): void
    {
        $this->response->getBody()->write("hello\n");
    }

    /**
     * @RequestMapping("/index")
     * @PreAuthorize(any={"book:view", "book:edit"})
     */
    public function index(): void
    {
        $this->response->getBody()->write(__METHOD__);
    }
}