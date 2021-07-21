<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\rpc\fixtures\HelloService;
use PHPUnit\Framework\TestCase;

class ProxyGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $generator = new ProxyGenerator(new ReflectionDocBlockFactory());
        $result = $generator->generate(HelloService::class);
        echo $result->getCode();
    }
}
