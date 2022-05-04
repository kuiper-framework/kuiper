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

namespace kuiper\web\attribute;

use Attribute;
use kuiper\di\attribute\ComponentTrait;
use kuiper\di\Component;

#[Attribute(Attribute::TARGET_CLASS)]
class ErrorHandler implements Component
{
    use ComponentTrait;

    /**
     * @param string|string[] $exceptions Exception class name
     */
    public function __construct(
        private readonly string|array $exceptions)
    {
    }

    /**
     * @return string[]
     */
    public function getExceptions(): array
    {
        return (array) $this->exceptions;
    }
}
