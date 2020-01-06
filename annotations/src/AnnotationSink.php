<?php

namespace kuiper\annotations;

class AnnotationSink
{
    /**
     * @var array
     */
    private $classAnnotations;

    /**
     * @var array
     */
    private $methodAnnotations;

    /**
     * @var array
     */
    private $propertyAnnotations;

    /**
     * AnnotationSink constructor.
     *
     * @param array $classAnnotations
     * @param array $methodAnnotations
     * @param array $propertyAnnotations
     */
    public function __construct(array $classAnnotations, array $methodAnnotations, array $propertyAnnotations)
    {
        $this->classAnnotations = $classAnnotations;
        $this->methodAnnotations = $methodAnnotations;
        $this->propertyAnnotations = $propertyAnnotations;
    }

    public static function emptySink()
    {
        return new self([], [], []);
    }

    /**
     * @return array
     */
    public function getClassAnnotations(): array
    {
        return $this->classAnnotations;
    }

    /**
     * @param string $methodName
     *
     * @return array
     */
    public function getMethodAnnotations(string $methodName): array
    {
        return isset($this->methodAnnotations[$methodName]) ? $this->methodAnnotations[$methodName] : [];
    }

    /**
     * @param string $propertyName
     *
     * @return array
     */
    public function getPropertyAnnotations(string $propertyName): array
    {
        return isset($this->propertyAnnotations[$propertyName]) ? $this->propertyAnnotations[$propertyName] : [];
    }

    /**
     * @param string $annotationName
     *
     * @return mixed
     */
    public function getFirstClassAnnotation(string $annotationName)
    {
        return $this->getFirstAnnotation($this->getClassAnnotations(), $annotationName);
    }

    /**
     * @param string $methodName
     * @param string $annotationName
     *
     * @return mixed
     */
    public function getFirstMethodAnnotation(string $methodName, string $annotationName)
    {
        return $this->getFirstAnnotation($this->getMethodAnnotations($methodName), $annotationName);
    }

    /**
     * @param string $propertyName
     * @param string $annotationName
     *
     * @return mixed
     */
    public function getFirstPropertyAnnotation(string $propertyName, string $annotationName)
    {
        return $this->getFirstAnnotation($this->getPropertyAnnotations($propertyName), $annotationName);
    }

    /**
     * Gets the first Annotation that is instance of give annotation class.
     *
     * @param array  $annotations
     * @param string $annotationName
     *
     * @return mixed
     */
    private function getFirstAnnotation(array $annotations, string $annotationName)
    {
        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }
}
