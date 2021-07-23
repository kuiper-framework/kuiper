<?php

declare(strict_types=1);

namespace kuiper\annotations;

interface AnnotationHandlerInterface
{
    /**
     * Sets the annotated target.
     *
     * @param \ReflectionClass|\ReflectionProperty|\ReflectionMethod $target
     */
    public function setTarget($target): void;

    /**
     * Gets the annotated target.
     *
     * @return \ReflectionClass|\ReflectionProperty|\ReflectionMethod
     */
    public function getTarget();

    /**
     * Processes the annotation.
     */
    public function handle(): void;
}
