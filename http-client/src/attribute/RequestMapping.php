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

namespace kuiper\http\client\annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class RequestMapping
{
    /**
     * The path mapping URI.
     *
     * @var string
     */
    public $value;

    /**
     * The HTTP request methods to map to.
     *
     * @var string
     */
    public $method;

    /**
     * @var array
     */
    public $queryParams;
}
