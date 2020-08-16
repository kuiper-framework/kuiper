<?php

declare(strict_types=1);

namespace kuiper\helper\view;

use kuiper\web\TestCase;
use kuiper\web\view\TwigView;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigViewTest extends TestCase
{
    public function testRender()
    {
        $arrayLoader = new ArrayLoader();
        $arrayLoader->setTemplate('index.html', 'hello');
        $twigView = new TwigView(new Environment($arrayLoader));
        $result = $twigView->render('index');
        $this->assertEquals('hello', $result);
        $result = $twigView->render('index.html');
        $this->assertEquals('hello', $result);
    }
}
