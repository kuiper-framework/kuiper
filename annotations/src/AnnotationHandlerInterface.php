<?php

declare(strict_types=1);

namespace kuiper\annotations;

interface AnnotationHandlerInterface
{
    public function setTarget($reflector): void;

    public function getTarget();

    public function handle(): void;
}
