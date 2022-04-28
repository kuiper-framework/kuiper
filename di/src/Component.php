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

namespace kuiper\di;


interface Component
{
    /**
     * Sets the components bean name.
     */
    public function setComponentId(string $name): void;

    /**
     * @return string
     */
    public function getComponentId(): string;

    /**
     * @return \Reflector
     */
    public function getTarget(): \Reflector;

    /**
     * @param \Reflector $target
     */
    public function setTarget(\Reflector $target): void;

    public function handle(): void;
}
