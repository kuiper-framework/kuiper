<?php

declare(strict_types=1);

namespace kuiper\web;

interface AnnotationProcessorInterface
{
    /**
     * Processes the annotation.
     */
    public function process(): void;
}
