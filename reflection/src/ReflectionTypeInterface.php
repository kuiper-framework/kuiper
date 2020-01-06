<?php

namespace kuiper\reflection;

interface ReflectionTypeInterface
{
    /**
     * Gets type string.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function __toString(): string;
}
