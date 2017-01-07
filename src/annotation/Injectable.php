<?php

namespace kuiper\di\annotation;

use kuiper\di\Scope;

/**
 * "Injectable" annotation.
 *
 * Marks a class as injectable
 *
 * @Annotation
 * @Target({"CLASS"})
 */
final class Injectable
{
    const SINGLETON = Scope::SINGLETON;
    const REQUEST = Scope::REQUEST;
    const PROTOTYPE = Scope::PROTOTYPE;

    /**
     * The scope of an class: prototype, singleton.
     *
     * @Enum
     */
    public $scope;

    /**
     * @var bool
     */
    public $lazy = false;
}
