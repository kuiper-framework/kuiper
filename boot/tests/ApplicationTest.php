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

        $ret = [
            $app->get('foo'),
            $app->getContainer()->withNamespace('kuiper\boot\fixtures\app1')->get('foo'),
            $app->getContainer()->withNamespace('kuiper\boot\fixtures\app2')->get('foo'),
            $app->getContainer()->withNamespace('kuiper\boot\fixtures\app1')
            ->get(\kuiper\boot\fixtures\app1\Foo::class)
            ->getFoo(),
            $app->getContainer()->withNamespace('kuiper\boot\fixtures\app2')
            ->get(\kuiper\boot\fixtures\app2\Foo::class)
            ->getFoo(),
        ];

        $this->assertEquals($ret, [
            'app1_foo',
            'app1_foo',
            'app2_foo',
            'app1_foo',
            'app2_foo',
        ]);

        // print_r($app->getSettings());
    }
}
