<?php

namespace kuiper\annotations;

use kuiper\reflection\ReflectionFileFactoryInterface;

class Parser implements ParserInterface
{
    /**
     * @var DocLexer
     */
    private $docLexer;

    /**
     * @var ReflectionFileFactoryInterface
     */
    private $reflectionFileFactory;

    public function __construct(ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        DocUtils::checkDocReadability();

        $this->reflectionFileFactory = $reflectionFileFactory;
        $this->docLexer = new DocLexer();
    }

    /**
     * {@inheritdoc}
     */
    public function parse(\ReflectionClass $class)
    {
        $parser = new AnnotationParser($this->docLexer, $this->reflectionFileFactory);
        $classAnnotations = $this->parseClassAnnotations($parser, $class);
        $propertyAnnotations = $this->parsePropertyAnnotations($parser, $class);
        $methodAnnotations = $this->parseMethodAnnotations($parser, $class);

        return new AnnotationSink($classAnnotations, $methodAnnotations, $propertyAnnotations);
    }

    /**
     * @param AnnotationParser $parser
     * @param \ReflectionClass $class
     *
     * @return array
     */
    private function parseClassAnnotations(AnnotationParser $parser, \ReflectionClass $class)
    {
        $comment = $class->getDocComment();
        if (!is_string($comment)) {
            return [];
        }

        return $parser->parse($comment, $class->getNamespaceName(), $class->getFileName(), $class->getStartLine());
    }

    /**
     * @param AnnotationParser $parser
     * @param \ReflectionClass $class
     *
     * @return array
     */
    private function parsePropertyAnnotations(AnnotationParser $parser, \ReflectionClass $class)
    {
        $annotations = [];
        foreach ($class->getProperties() as $property) {
            // properties start line is not available
            $comment = $property->getDocComment();
            if (!is_string($comment)) {
                continue;
            }
            $declaringClass = $property->getDeclaringClass();
            $annotations[$property->getName()] = $parser->parse(
                $comment,
                $declaringClass->getNamespaceName(),
                $declaringClass->getFileName(),
                $declaringClass->getStartLine()
            );
        }

        return $annotations;
    }

    /**
     * @param AnnotationParser $parser
     * @param \ReflectionClass $class
     *
     * @return array
     */
    private function parseMethodAnnotations(AnnotationParser $parser, \ReflectionClass $class)
    {
        $annotations = [];
        foreach ($class->getMethods() as $method) {
            $comment = $method->getDocComment();
            if (!is_string($comment)) {
                continue;
            }
            $declaringClass = $method->getDeclaringClass();
            $annotations[$method->getName()] = $parser->parse(
                $comment,
                $declaringClass->getNamespaceName(),
                $method->getFileName(),
                $method->getStartLine()
            );
        }

        return $annotations;
    }
}
