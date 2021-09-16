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

namespace kuiper\tars\annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
final class TarsParameter
{
    /**
     * @Required
     *
     * @var string
     */
    public $name;
    /**
     * @Required()
     *
     * @var string
     */
    public $type;
    /**
     * @var int
     */
    public $order;
    /**
     * @var bool
     */
    public $required;
    /**
     * @var bool
     */
    public $out;
    /**
     * @var bool
     */
    public $routeKey;
}
