<?php

namespace kuiper\di\source;

use kuiper\di\TestCase;

class DotArraySourceTest extends TestCase
{
    public function createResolver()
    {
        $resolver = new DotArraySource([
            'db' => [
                'host' => 'localhost',
            ],
        ]);

        return $resolver;
    }

    public function testResolver()
    {
        $resolver = $this->createResolver();
        $this->assertTrue($resolver->has('db.host'));
        $this->assertTrue($resolver->has('settings.db.host'));
        $this->assertFalse($resolver->has('db'));
        $this->assertTrue($resolver->has('settings.db'));
        $this->assertEquals('localhost', $resolver->get('db.host')->getDefinition()->getValue());
    }
}
