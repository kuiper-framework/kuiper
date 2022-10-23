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
use Laminas\Code\Generator\AbstractMemberGenerator;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Reflection\ParameterReflection;

class ConnectionProxyGenerator
{
    /**
     * @template T
     *
     * @param PoolFactoryInterface $poolFactory
     * @param class-string<T>      $className
     * @param callable             $connectionFactory
     *
     * @return T
     */
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

        $phpClass->addProperty('pool', null, AbstractMemberGenerator::FLAG_PRIVATE);
        $phpClass->addMethod('__construct',
            [
                [
                    'type' => PoolInterface::class,
                    'name' => 'pool',
                ],
            ],
            AbstractMemberGenerator::FLAG_PUBLIC,
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
            AbstractMemberGenerator::FLAG_PUBLIC,
            implode("\n", [
                'return \kuiper\swoole\pool\PoolHelper::call($this->pool, function($conn) use ($method, $args) {',
                'return $conn->$method(...$args);',
                '});',
            ])
        );
        $phpClass->addMethod('__destruct');

        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if ($reflectionMethod->isStatic() || in_array($reflectionMethod->getName(), ['__construct', '__destruct'], true)) {
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
            $return = ($returnType instanceof VoidType ? '' : 'return ');
            $useArgs = empty($params) ? '' : implode(', ', array_map(static function (ParameterGenerator $param) {
                return ($param->getPassedByReference() ? '&' : '').'$'.$param->getName();
            }, $params));
            $args = empty($params) ? '' : implode(', ', array_map(static function (ParameterGenerator $param) {
                if ($param->getVariadic()) {
                    return '...$'.$param->getName();
                }

                return '$'.$param->getName();
            }, $params));
            $stmts = [
                $return.'\kuiper\swoole\pool\PoolHelper::call($this->pool, function($conn)'.(empty($useArgs) ? '' : " use ($useArgs)").' {',
                $return.sprintf('$conn->%s(%s);', $reflectionMethod->getName(), $args),
                '});',
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
