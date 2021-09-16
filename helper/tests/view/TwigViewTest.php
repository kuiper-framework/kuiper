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
