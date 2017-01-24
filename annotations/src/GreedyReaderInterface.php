<?php

namespace kuiper\annotations;

use ReflectionClass;

interface GreedyReaderInterface
{
    /**
     * Gets all annotations.
     *
     * @param ReflectionClass $class the ReflectionClass of the class from which
     *                               the class annotations should be read
     *
     * @return array contains
     *               - class
     *               - methods
     *               - properties
     */
    public function getAnnotations(ReflectionClass $class);
}
