<?php

namespace kuiper\annotations;

use ReflectionClass;

interface ParserInterface
{
    /**
     * Parses annotations from class.
     *
     * @param ReflectionClass $class
     *
     * @return array
     */
    public function parse(ReflectionClass $class);
}
