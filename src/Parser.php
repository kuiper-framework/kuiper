<?php
namespace kuiper\annotations;

use kuiper\annotations\exception\AnnotationException;
use kuiper\reflection\ReflectionFile;
use ReflectionClass;

class Parser implements ParserInterface
{
    /**
     * @var DocParser
     */
    private $docParser;
    
    public function __construct()
    {
        $this->docParser = new DocParser();
    }
    
    /**
     * @inheritDoc
     */
    public function parse(ReflectionClass $class)
    {
        $annotations = [];
        $comment = $class->getDocComment();
        if (is_string($comment)) {
            $annotations['class'] = $this->docParser->parse(
                $comment,
                new ReflectionFile($class->getFileName()),
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
                    new ReflectionFile($declaringClass->getFileName()),
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
                    new ReflectionFile($method->getFileName()),
                    $declaringClass->getNamespaceName(),
                    $method->getStartLine()
                );
            }
        }
        return $annotations;
    }
}
