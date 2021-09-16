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

namespace kuiper\di\annotation;

use kuiper\annotations\AnnotationHandlerInterface;

interface ComponentInterface extends AnnotationHandlerInterface
{
    /**
     * Sets the components bean name.
     */
    public function setComponentId(string $name): void;

    /**
     * @return string
     */
    public function getComponentId(): string;
}
