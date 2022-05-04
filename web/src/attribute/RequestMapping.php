<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\web\annotation;

/**
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
class RequestMapping
{
    /**
     * The path mapping URIs. type is string|string[].
     *
     * @var mixed
     */
    public $value;

    /**
     * Assign a name to this mapping.
     *
     * @var string
     */
    public $name;

    /**
     * The HTTP request methods to map to.
     *
     * @var string[]
     */
    public $method;
}
