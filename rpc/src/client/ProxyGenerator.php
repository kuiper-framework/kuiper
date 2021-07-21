<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\helper\Text;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\reflection\type\VoidType;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Reflection\DocBlockReflection;

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

    public function __construct(ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory)
    {
        $this->reflectionDocBlockFactory = $reflectionDocBlockFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $interfaceName, array $context = []): GeneratedClass
    {
        $phpClass = $this->createClassGenerator($interfaceName);

        $className = ltrim($phpClass->getNamespaceName().'\\'.$phpClass->getName(), '\\');
        self::$PROXY_INTERFACES[$className] = $interfaceName;

        return new GeneratedClass($phpClass->getNamespaceName().'\\'.$phpClass->getName(), $phpClass->generate());
    }

    private function createParameter(\ReflectionParameter $parameter): array
    {
        $parameterType = $parameter->getType();

        return array_filter([
            'name' => $parameter->getName(),
            'type' => isset($parameterType) ? ($parameter->allowsNull() ? '?' : '').$parameterType : null,
            'PassedByReference' => $parameter->isPassedByReference(),
        ]);
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
        $call = '$this->client->sendRequest($this->client->createRequest($this, __FUNCTION__, ['.
            (empty($parameters) ? '' : $this->buildParameters($parameters)).']));';
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

    /**
     * @throws \ReflectionException
     */
    public function createClassGenerator(string $interfaceName): ClassGenerator
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
        $phpClass->addProperty('client', null, PropertyGenerator::FLAG_PRIVATE);
        $phpClass->addMethod('__construct',
            [
                [
                    'type' => RpcClientInterface::class,
                    'name' => 'client',
                ],
            ],
            MethodGenerator::FLAG_PUBLIC,
            '$this->client = $client;'
        );

        $methodCreateExecutor = 'createExecutor';
        foreach ($class->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->getName() === $methodCreateExecutor) {
                $methodCreateExecutor = null;
            }

            $methodBody = $this->createBody($reflectionMethod);
            $methodGenerator = new MethodGenerator(
                $reflectionMethod->getName(),
                array_map(function ($parameter): array {
                    return $this->createParameter($parameter);
                }, $reflectionMethod->getParameters()),
                MethodGenerator::FLAG_PUBLIC,
                $methodBody,
                DocBlockGenerator::fromReflection(new DocBlockReflection('/** @inheritdoc */'))
            );
            /* @phpstan-ignore-next-line */
            $methodGenerator->setReturnType($reflectionMethod->getReturnType());
            $phpClass->addMethodFromGenerator($methodGenerator);
        }
        if (null !== $methodCreateExecutor) {
            $phpClass->addMethodFromGenerator($this->createRpcExecutorMethod($methodCreateExecutor));
        }

        return $phpClass;
    }

    public static function getInterfaceName(string $proxyClass): ?string
    {
        return self::$PROXY_INTERFACES[$proxyClass] ?? null;
    }

    private function createRpcExecutorMethod(string $methodName): MethodGenerator
    {
        $argsParam = new ParameterGenerator();
        $argsParam->setName('args');
        $argsParam->setVariadic(true);
        $methodGenerator = new MethodGenerator(
            $methodName,
            [
                ['name' => 'method', 'type' => 'string'],
                $argsParam,
            ],
            MethodGenerator::FLAG_PUBLIC,
            'return new \\'.RpcExecutor::class.'($this->client, $this->client->createRequest($this, $method, $args));'
        );
        $methodGenerator->setReturnType(RpcExecutorInterface::class);

        return $methodGenerator;
    }
}
