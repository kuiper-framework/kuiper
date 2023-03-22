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
use kuiper\web\attribute\LoginOnly;
use kuiper\web\attribute\RequestMapping;

#[Controller]
class IndexController extends AbstractController
{
    #[GetMapping('/')]
    #[LoginOnly]
    public function home(): void
    {
        $this->response->getBody()->write("hello\n");
    }
    #[GetMapping('/index')]
    public function index(): void
    {
        $this->response->getBody()->write(__METHOD__);
    }
}
