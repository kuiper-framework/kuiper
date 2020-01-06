<?php

namespace kuiper\annotations;

interface AnnotationHandlerInterface
{
    public function setTarget($reflector): void;

    public function handle(): void;
}
