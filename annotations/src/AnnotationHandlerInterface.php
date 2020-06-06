<?php

declare(strict_types=1);

namespace kuiper\annotations;

interface AnnotationHandlerInterface
{
    /**
     * Sets the annotated target.
     *
     * @param \Reflector $target
     */
    public function setTarget($target): void;

    /**
     * Gets the annotated target.
     *
     * @return \Reflector
     */
    public function getTarget();

    /**
     * Processes the annotation.
     */
    public function handle(): void;
}
