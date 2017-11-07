<?php

namespace kuiper\annotations;

interface GreedyReaderInterface
{
    /**
     * Gets all annotations.
     *
     * @param \ReflectionClass $class the ReflectionClass of the class from which
     *                                the class annotations should be read
     *
     * @return AnnotationSink
     */
    public function getAnnotations(\ReflectionClass $class);
}
