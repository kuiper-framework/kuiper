<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\web\fixtures\controllers;

use kuiper\di\attribute\Controller;
use kuiper\web\AbstractController;
use kuiper\web\attribute\GetMapping;
use kuiper\web\attribute\PreAuthorize;
use kuiper\web\attribute\RequestMapping;

#[Controller]
#[RequestMapping("/auth")]
class TestAuthController extends AbstractController
{
    #[GetMapping("/home")]
    #[PreAuthorize(["book:view", "book:edit"])]
    public function home(): void
    {
        $this->response->getBody()->write("hello\n");
    }

    #[RequestMapping("/index")]
    #[PreAuthorize([], ["book:view", "book:edit"])]
    public function index(): void
    {
        $this->response->getBody()->write(__METHOD__);
    }
}
