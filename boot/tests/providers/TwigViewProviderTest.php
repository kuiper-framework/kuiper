<?php

namespace kuiper\boot\providers;

use kuiper\boot\Application;
use kuiper\boot\TestCase;
use kuiper\web\TwigView;
use kuiper\web\ViewInterface;

class MonoLoggerProviderTest extends TestCase
{
    public function testLogger()
    {
        $app = new Application();
        $app->getSettings()->merge([
            'app' => [
                'providers' => [
                    TwigViewProvider::class,
                ],
                'dev_mode' => true,
            ],
        ]);
        $app->bootstrap();
        $view = $app->get(ViewInterface::class);
        // print_r($logger);
        $this->assertInstanceOf(ViewInterface::class, $view);
        $this->assertInstanceOf(TwigView::class, $view);
    }
}
