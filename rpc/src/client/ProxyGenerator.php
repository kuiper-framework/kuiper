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

namespace kuiper\rpc\client;

use kuiper\helper\Text;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\reflection\type\VoidType;
use kuiper\swoole\pool\GeneratedClass;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Reflection\DocBlockReflection;
use Laminas\Code\Reflection\ParameterReflection;

class ProxyGenerator implements ProxyGeneratorInterface
{
    /**
     * @var string[]
     */
    private static $PROXY_INTERFACES = [];

    /**
     * @var ReflectionDocBlockFactoryInterface
     */
    private $reflectionDocBlockFactory;

    public function __construct(?ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory = null)
    {
        $this->reflectionDocBlockFactory = $reflectionDocBlockFactory ?? ReflectionDocBlockFactory::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $interfaceName, array $context = []): GeneratedClass
    {
        $phpClass = $this->createClassGenerator($interfaceName, $context);

        $className = ltrim($phpClass->getNamespaceName().'\\'.$phpClass->getName(), '\\');
        self::$PROXY_INTERFACES[$className] = $interfaceName;

        return new GeneratedClass($phpClass->getNamespaceName().'\\'.$phpClass->getName(), $phpClass->generate());
    }

    /**
     * @throws \ReflectionException
     */
    protected function createClassGenerator(string $interfaceName, array $context = []): ClassGenerator
    {
        $class = new \ReflectionClass($interfaceName);
        if (!$class->isInterface()) {
            throw new \InvalidArgumentException("$interfaceName should be an interface");
        }
        if (false !== $class->getFileName()) {
            $hash = md5_file($class->getFileName());
        } else {
            $hash = md5(uniqid('', true));
        }
        $phpClass = new ClassGenerator(
            $class->getShortName().$hash,
            Text::isNotEmpty($class->getNamespaceName()) ? $class->getNamespaceName() : null
        );

        $phpClass->setImplementedInterfaces([$class->getName()]);
        $phpClass->addProperty('rpcExecutorFactory', null, PropertyGenerator::FLAG_PRIVATE);
        $phpClass->addMethod('__construct',
            [
                [
                    'type' => RpcExecutorFactoryInterface::class,
                    'name' => 'rpcExecutorFactory',
                ],
            ],
            MethodGenerator::FLAG_PUBLIC,
            '$this->rpcExecutorFactory = $rpcExecutorFactory;'
        );

        foreach ($class->getMethods() as $reflectionMethod) {
            $methodBody = $this->createBody($reflectionMethod);
            $methodGenerator = new MethodGenerator(
                $reflectionMethod->getName(),
                array_map(function ($parameter) use ($reflectionMethod): ParameterGenerator {
                    return $this->createParameter($reflectionMethod, $parameter);
                }, $reflectionMethod->getParameters()),
                MethodGenerator::FLAG_PUBLIC,
                $methodBody,
                DocBlockGenerator::fromReflection(new DocBlockReflection('/** @inheritdoc */'))
            );
            /* @phpstan-ignore-next-line */
            $methodGenerator->setReturnType($reflectionMethod->getReturnType());
            $phpClass->addMethodFromGenerator($methodGenerator);
        }
        if (!$class->hasMethod('getRpcExecutorFactory')) {
            $methodGenerator = new MethodGenerator(
                'getRpcExecutorFactory',
                [],
                MethodGenerator::FLAG_PUBLIC,
                'return $this->rpcExecutorFactory;'
            );
            $methodGenerator->setReturnType(RpcExecutorFactoryInterface::class);
            $phpClass->addMethodFromGenerator($methodGenerator);
        }

        return $phpClass;
    }

    private function createParameter(\ReflectionMethod $method, \ReflectionParameter $parameter): ParameterGenerator
    {
        return ParameterGenerator::fromReflection(new ParameterReflection(
            [$method->getDeclaringClass()->getName(), $method->getName()], $parameter->getName()
        ));
    }

    private function createBody(\ReflectionMethod $reflectionMethod): string
    {
        $parameters = [];
        $outParameters = [];
        $returnType = $this->reflectionDocBlockFactory->createMethodDocBlock($reflectionMethod)->getReturnType();
        $hasReturnValue = !($returnType instanceof VoidType);

        foreach ($reflectionMethod->getParameters() as $parameter) {
            if ($parameter->isPassedByReference()) {
                $outParameters[] = $parameter->name;
            } else {
                $parameters[] = $parameter->name;
            }
        }
        $returnValueName = 'ret';
        $i = 1;
        while (in_array($returnValueName, $outParameters, true)) {
            $returnValueName = 'ret'.$i++;
        }
        array_unshift($outParameters, $returnValueName);
        // 参数顺序 $returnValue, ...$out
        $call = '$this->rpcExecutorFactory->createExecutor($this, __FUNCTION__, ['.
            (empty($parameters) ? '' : $this->buildParameters($parameters)).'])->execute();';
        if (empty($outParameters)) {
            return $call;
        }
        $body = 'list ('.$this->buildParameters($outParameters).') = '.$call;
        if ($hasReturnValue) {
            $body .= "\nreturn $".$outParameters[0].';';
        }

        return $body;
    }

    private function buildParameters(array $parameters): string
    {
        return implode(', ', array_map(static function ($name): string {
            return '$'.$name;
        }, $parameters));
    }

    public static function getInterfaceName(string $proxyClass): ?string
    {
        return self::$PROXY_INTERFACES[$proxyClass] ?? null;
    }
}
