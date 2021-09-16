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

use kuiper\di\ComponentCollection;
use ReflectionClass;

trait ComponentTrait
{
    /**
     * @var ReflectionClass
     */
    protected $class;

    /**
     * @var string
     */
    protected $componentId;

    public function setTarget($class): void
    {
        /* @var ReflectionClass $class */
        $this->class = $class;
    }

    public function getTarget(): ReflectionClass
    {
        return $this->class;
    }

    public function getTargetClass(): string
    {
        return $this->class->getName();
    }

    public function setComponentId(string $componentId): void
    {
        $this->componentId = $componentId;
    }

    public function getComponentId(): string
    {
        return $this->componentId ?? $this->class->getName();
    }

    public function handle(): void
    {
        ComponentCollection::register($this);
    }
}
