<?php

declare(strict_types=1);

namespace kuiper\web;

use kuiper\web\view\TwigView;
use kuiper\web\view\ViewInterface;

class TwigViewTest extends TestCase
{
    public function testTwigView()
    {
        $view = $this->getContainer()->get(ViewInterface::class);
        $this->assertInstanceOf(TwigView::class, $view);
    }
}
