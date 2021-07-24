<?php

declare(strict_types=1);

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
