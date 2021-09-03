<?php

declare(strict_types=1);

namespace kuiper\swoole;

use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testCreate()
    {
        $_SERVER['APP_PATH'] = dirname(__DIR__, 2);
        $app = Application::create();
        $this->assertEquals($app->getConfig()['application']->toArray(), [
            'env' => 'prod',
        ]);
    }

    public function testCreateWithConfigFile()
    {
        $_SERVER['APP_PATH'] = dirname(__DIR__, 2);
        $_SERVER['argv'] = ['--config', __DIR__.'/fixtures/config.ini'];
        $app = Application::create();
        $this->assertEquals($app->getConfig()['application']->toArray(), [
            'env' => 'dev',
        ]);
    }

    public function testCreateWithCommandOption()
    {
        $_SERVER['APP_PATH'] = dirname(__DIR__, 2);
        $_SERVER['argv'] = ['--define', 'env=dev'];
        $app = Application::create();
        $this->assertEquals($app->getConfig()['application']->toArray(), [
            'env' => 'dev',
        ]);
    }
}
