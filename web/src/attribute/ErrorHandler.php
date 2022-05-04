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

use Doctrine\Common\Annotations\Annotation\Target;
use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ErrorHandler implements ComponentInterface
{
    use ComponentTrait;

    /**
     * Exception class name, can be string or string[].
     *
     * @var mixed
     */
    public $value;
}
