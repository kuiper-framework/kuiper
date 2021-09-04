<?php

declare(strict_types=1);

namespace kuiper\web;

use kuiper\web\view\PhpView;
use kuiper\web\view\ViewInterface;

class PhpViewTest extends TestCase
{
    protected function getConfig(): array
    {
        return [
            'application' => [
                'web' => [
                    'view' => [
                        'engine' => 'php',
                    ],
                ],
            ],
        ];
    }

    public function testPhpView()
    {
        $view = $this->getContainer()->get(ViewInterface::class);
        $this->assertInstanceOf(PhpView::class, $view);
    }
}
