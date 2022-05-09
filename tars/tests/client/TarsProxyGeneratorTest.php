<?php

namespace kuiper\tars\client;

use kuiper\tars\fixtures\HelloService;
use PHPUnit\Framework\TestCase;

class TarsProxyGeneratorTest extends TestCase
{
    public function testName()
    {
        $generator = new TarsProxyGenerator();
        $class = $generator->generate(HelloService::class, [
            'service' => 'demo.app.HelloObj'
        ]);
        // echo $class->getCode();
        $this->assertStringEqualsFile(__DIR__ . '/../fixtures/HelloServiceProxy.txt', trim($class->getCode()));
    }

}