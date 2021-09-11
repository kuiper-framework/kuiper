<?php

declare(strict_types=1);

namespace kuiper\cache;

use kuiper\swoole\pool\SingleConnectionPool;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Reflection\ParameterReflection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisTest extends TestCase
{
    public function testGet()
    {
        $redisPool = new SingleConnectionPool('redis', function () {
            return RedisAdapter::createConnection(sprintf('redis://%s?dbindex=%d',
                getenv('REDIS_HOST') ?: 'localhost',
                getenv('REDIS_DATABASE') ?: 1));
        });
        $redis = new Redis($redisPool);
        $ret = $redis->get('foo');
        var_export($ret);
    }

    public function testName()
    {
        $phpClass = new ClassGenerator('Redis');

        $class = new \ReflectionClass(\Redis::class);
        foreach ($class->getMethods() as $reflectionMethod) {
            $params = array_map(function ($parameter) use ($reflectionMethod) {
                return $this->createParameter($reflectionMethod, $parameter);
            }, $reflectionMethod->getParameters());
            $methodBody = sprintf('return $this->pool->take()->%s(%s);', $reflectionMethod->getName(),
                empty($params) ? '' : implode(', ', array_map(function ($param) {
                    if ($param->getVariadic()) {
                        return '...$'.$param->getName();
                    }

                    return '$'.$param->getName();
                }, $params)));
            $methodGenerator = new MethodGenerator(
                $reflectionMethod->getName(),
                $params,
                MethodGenerator::FLAG_PUBLIC,
                $methodBody
            );
            /* @phpstan-ignore-next-line */
            $methodGenerator->setReturnType($reflectionMethod->getReturnType());
            $phpClass->addMethodFromGenerator($methodGenerator);
        }
        echo $phpClass->generate();
    }

    private function createParameter(\ReflectionMethod $method, \ReflectionParameter $parameter)
    {
        return ParameterGenerator::fromReflection(new ParameterReflection([$method->getDeclaringClass()->getName(), $method->getName()], $parameter->getName()));
    }
}
