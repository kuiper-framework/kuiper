<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\swoole\pool;

use kuiper\helper\Text;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\type\MixedType;
use kuiper\reflection\type\VoidType;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Reflection\ParameterReflection;

class ConnectionProxyGenerator
{
    public static function create(PoolFactoryInterface $poolFactory, string $className, callable $connectionFactory): object
    {
        $generator = new self();
        $result = $generator->generate($className);
        $result->eval();
        $proxyClass = $result->getClassName();

        return new $proxyClass($poolFactory->create($className, $connectionFactory));
    }

    public function generate(string $className): GeneratedClass
    {
        $phpClass = $this->createClassGenerator($className);

        return new GeneratedClass($phpClass->getNamespaceName().'\\'.$phpClass->getName(), $phpClass->generate());
    }

    /**
     * @throws \ReflectionException
     */
    protected function createClassGenerator(string $className): ClassGenerator
    {
        $class = new \ReflectionClass($className);
        if (false !== $class->getFileName()) {
            $hash = md5_file($class->getFileName());
        } else {
            $hash = md5(uniqid('', true));
        }
        $phpClass = new ClassGenerator(
            $class->getShortName().$hash,
            Text::isNotEmpty($class->getNamespaceName()) ? $class->getNamespaceName() : null
        );
        if ($class->isInterface()) {
            $phpClass->setImplementedInterfaces([$class->getName()]);
        } else {
            $phpClass->setExtendedClass($className);
        }

        $phpClass->addProperty('pool', null, PropertyGenerator::FLAG_PRIVATE);
        $phpClass->addMethod('__construct',
            [
                [
                    'type' => PoolInterface::class,
                    'name' => 'pool',
                ],
            ],
            MethodGenerator::FLAG_PUBLIC,
            '$this->pool = $pool;'
        );
        $phpClass->addMethod('__call',
            [
                [
                    'type' => 'string',
                    'name' => 'method',
                ],
                [
                    'type' => 'array',
                    'name' => 'args',
                ],
            ],
            MethodGenerator::FLAG_PUBLIC,
            implode("\n", [
                '$ret = $this->pool->take()->getResource()->$method(...$args);',
                '$this->pool->release();',
                'return $ret;',
            ])
        );
        $phpClass->addMethod('__destruct');

        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if (in_array($reflectionMethod->getName(), ['__construct', '__destruct'], true) || $reflectionMethod->isStatic()) {
                continue;
            }
            $params = array_map(function ($parameter) use ($reflectionMethod): ParameterGenerator {
                return $this->createParameter($reflectionMethod, $parameter);
            }, $reflectionMethod->getParameters());
            try {
                $returnType = ReflectionDocBlockFactory::getInstance()->createMethodDocBlock($reflectionMethod)
                    ->getReturnType();
            } catch (\Exception $e) {
                $returnType = $reflectionMethod->hasReturnType() ? ReflectionType::fromPhpType($reflectionMethod->getReturnType()) : new MixedType();
            }
            $stmts = [
                sprintf('$ret = $this->pool->take()->getResource()->%s(%s);', $reflectionMethod->getName(),
                    empty($params) ? '' : implode(', ', array_map(static function (ParameterGenerator $param) {
                        if ($param->getVariadic()) {
                            return '...$'.$param->getName();
                        }

                        return '$'.$param->getName();
                    }, $params))),
                '$this->pool->release();',
                ($returnType instanceof VoidType ? '' : 'return $ret;'),
            ];
            $methodBody = implode("\n", $stmts);
            $methodGenerator = new MethodGenerator(
                $reflectionMethod->getName(),
                $params,
                MethodGenerator::FLAG_PUBLIC,
                $methodBody
            );
            if (null !== $reflectionMethod->getReturnType()) {
                $methodGenerator->setReturnType(ReflectionType::phpTypeAsString($reflectionMethod->getReturnType()));
            }
            $phpClass->addMethodFromGenerator($methodGenerator);
        }

        return $phpClass;
    }

    private function createParameter(\ReflectionMethod $method, \ReflectionParameter $parameter): ParameterGenerator
    {
        $callable = [$method->getDeclaringClass()->getName(), $method->getName()];

        return ParameterGenerator::fromReflection(new ParameterReflection($callable, $parameter->getName()));
    }
}
