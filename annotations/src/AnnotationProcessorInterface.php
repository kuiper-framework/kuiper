<?php

namespace kuiper\annotations;

interface AnnotationProcessorInterface
{
    /**
     * Process annotation.
     *
     * @param \Reflector $reflector
     * @param object     $annotation
     */
    public function process($reflector, $annotation): void;
}
