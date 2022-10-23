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

namespace kuiper\di\attribute;

use kuiper\di\ComponentCollection;
use ReflectionClass;

trait ComponentTrait
{
    protected ReflectionClass $class;

    protected ?string $componentId;

    /**
     * {@inheritDoc}
     */
    public function setTarget(\Reflector $class): void
    {
        if (!$class instanceof \ReflectionClass) {
            throw new \InvalidArgumentException(sprintf('Attribute %s only target class', get_class($this)));
        }
        $this->class = $class;
    }

    /**
     * @return \Reflector
     */
    public function getTarget(): \Reflector
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
