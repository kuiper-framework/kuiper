<?php

declare(strict_types=1);

namespace kuiper\annotations;

trait AnnotationReaderAwareTrait
{
    /**
     * @var AnnotationReaderInterface|null
     */
    protected $annotationReader;

    public function setAnnotationReader(AnnotationReaderInterface $annotationReader): void
    {
        $this->annotationReader = $annotationReader;
    }
}
