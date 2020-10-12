<?php

declare(strict_types=1);

namespace kuiper\http\client;

use kuiper\helper\Text;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Reflection\MethodReflection;

class ProxyGeneratorImpl implements ProxyGenerator
{
    /**
     * @var bool
     */
    private $eval;

    public function __construct(bool $eval = true)
    {
        $this->eval = $eval;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $clientClassName): string
    {
        $phpClass = $this->createClassGenerator($clientClassName);
        $this->load($phpClass);

        return $phpClass->getNamespaceName().'\\'.$phpClass->getName();
    }

    private function load(ClassGenerator $phpClass): void
    {
        $code = $phpClass->generate();
        // echo $code, "\n";
        if ($this->eval) {
            eval($code);
        } else {
            $fileName = tempnam(sys_get_temp_dir(), 'HttpClientProxyGenerator.php.tmp.');

            file_put_contents($fileName, "<?php\n".$code);
            /* @noinspection PhpIncludeInspection */
            require $fileName;
            unlink($fileName);
        }
    }

    private function createBody(\ReflectionMethod $reflectionMethod): string
    {
        $parameters = [];

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameters[] = $parameter->getName();
        }
        $body = sprintf(
            '$this->client->call(\'%s\', __FUNCTION__%s);',
            addslashes($reflectionMethod->getDeclaringClass()->getName()),
            (0 === count($parameters) ? '' : ', '.$this->buildParameters($parameters)));

        $returnType = $reflectionMethod->getReturnType();
        if ((null !== $returnType && 'void' !== $returnType->getName())
            || false !== strpos($reflectionMethod->getDocComment(), '@return')) {
            $body = 'return '.$body;
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
    public function createClassGenerator(string $clientClassName): ClassGenerator
    {
        $class = new \ReflectionClass($clientClassName);
        if (!$class->isInterface()) {
            throw new \InvalidArgumentException("$clientClassName should be an interface");
        }
        $phpClass = new ClassGenerator(
            $class->getShortName().'Client'.md5(uniqid('', true)),
            Text::isEmpty($class->getNamespaceName()) ? null : $class->getNamespaceName(),
            $flags = null,
            $extends = null,
            $interfaces = [],
            $properties = [],
            $methods = []
        );

        $phpClass->setImplementedInterfaces([$class->getName()]);
        $phpClass->addProperty('client', null, PropertyGenerator::FLAG_PRIVATE);
        $phpClass->addMethod('__construct',
            [
                [
                    'type' => HttpClientProxy::class,
                    'name' => 'client',
                ],
            ],
            MethodGenerator::FLAG_PUBLIC,
            '$this->client = $client;'
        );

        foreach ($class->getMethods() as $reflectionMethod) {
            $methodReflection = new MethodReflection($reflectionMethod->getDeclaringClass()->getName(), $reflectionMethod->getName());
            $methodGenerator = MethodGenerator::fromReflection($methodReflection);
            $methodGenerator->setBody($this->createBody($reflectionMethod));
            $methodGenerator->setInterface(false);
            $methodGenerator->setDocBlock('@inheritDoc');
            $methodGenerator->setSourceDirty();
            $phpClass->addMethodFromGenerator($methodGenerator);
        }

        return $phpClass;
    }
}
