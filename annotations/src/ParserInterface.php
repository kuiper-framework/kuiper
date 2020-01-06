<?php

namespace kuiper\annotations;

interface ParserInterface
{
    /**
     * Parses annotations from class.
     *
     * @param \ReflectionClass $class
     *
     * @return AnnotationSink
     */
    public function parse(\ReflectionClass $class);
}
