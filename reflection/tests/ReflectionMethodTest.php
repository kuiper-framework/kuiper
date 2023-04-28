<?php

declare(strict_types=1);

namespace kuiper\reflection;

use kuiper\reflection\fixtures\HttpClient;
use kuiper\reflection\type\MapType;
use ReflectionMethod;

class ReflectionMethodTest extends TestCase
{
    public function testArrayType()
    {
        $reflectionDocBlockFactory = ReflectionDocBlockFactory::getInstance();
        $reflectionMethodDocBlock = $reflectionDocBlockFactory->createMethodDocBlock(new ReflectionMethod(HttpClient::class, 'getUsers'));
        $returnType = $reflectionMethodDocBlock->getReturnType();
        $this->assertInstanceOf(MapType::class, $returnType);
    }
}
