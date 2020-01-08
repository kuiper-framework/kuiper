<?php

declare(strict_types=1);

namespace kuiper\annotations;

interface AnnotationReaderAwareInterface
{
    public function setAnnotationReader(AnnotationReaderInterface $annotationReader): void;
}
