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

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class ClassType extends ReflectionType
{
    /**
     * @var string
     */
    private $className;

    public function __construct(string $className, bool $allowsNull = false)
    {
        parent::__construct($allowsNull);
        $this->className = $className;
    }

    public function getName(): string
    {
        return $this->className;
    }

    public function isClass(): bool
    {
        return true;
    }
}
