<?php

namespace kuiper\boot;

class ApplicationTest extends TestCase
{
    public function testModule()
    {
        $app = new Application();
        $app->useAnnotations();
        $app->getSettings()->merge([
            'app' => [
                'providers' => [
                    \kuiper\boot\fixtures\app1\ServiceProvider::class,
                    \kuiper\boot\fixtures\app2\ServiceProvider::class,
                ],
            ],
        ]);
        $app->bootstrap();

        $this->assertEquals([
            $app->get('app1.name'),
            $app->get('app2.name'),
        ], ['app1', 'app2']);
    }
}
