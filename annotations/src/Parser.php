<?php

namespace kuiper\annotations;

use kuiper\reflection\ReflectionFileFactoryInterface;
use ReflectionClass;

class Parser implements ParserInterface
{
    /**
     * @var DocParser
     */
    private $docParser;

    /**
     * @var ReflectionFileFactoryInterface
     */
    private $reflectionFileFactory;

    public function __construct(ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        $this->reflectionFileFactory = $reflectionFileFactory;
        $this->docParser = new DocParser();
    }

    /**
     * {@inheritdoc}
     */
    public function parse(ReflectionClass $class)
    {
        $annotations = [];
        $comment = $class->getDocComment();
        if (is_string($comment)) {
            $annotations['class'] = $this->docParser->parse(
                $comment,
                $this->reflectionFileFactory->create($class->getFileName()),
                $class->getNamespaceName(),
                $class->getStartLine()
            );
        }
        foreach ($class->getProperties() as $property) {
            // properties start line is not available
            $comment = $property->getDocComment();
            if (is_string($comment)) {
                $declaringClass = $property->getDeclaringClass();
                $annotations['properties'][$property->getName()] = $this->docParser->parse(
                    $comment,
                    $this->reflectionFileFactory->create($declaringClass->getFileName()),
                    $declaringClass->getNamespaceName(),
                    $declaringClass->getStartLine()
                );
            }
        }
        foreach ($class->getMethods() as $method) {
            $comment = $method->getDocComment();
            if (is_string($comment)) {
                $declaringClass = $method->getDeclaringClass();
                $annotations['methods'][$method->getName()] = $this->docParser->parse(
                    $comment,
                    $this->reflectionFileFactory->create($method->getFileName()),
                    $declaringClass->getNamespaceName(),
                    $method->getStartLine()
                );
            }
        }

        return $annotations;
    }
}
