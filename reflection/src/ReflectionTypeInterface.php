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

namespace kuiper\reflection;

interface ReflectionTypeInterface extends TypeFilterInterface
{
    /**
     * Gets type string.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Checks if null is allowed.
     *
     * @return bool
     */
    public function allowsNull(): bool;

    public function withAllowsNull(bool $allowsNull): ReflectionTypeInterface;

    /**
     * @return string
     */
    public function __toString(): string;

    public function isPrimitive(): bool;

    public function isArray(): bool;

    public function isScalar(): bool;

    public function isCompound(): bool;

    public function isPseudo(): bool;

    public function isNull(): bool;

    public function isResource(): bool;

    public function isClass(): bool;

    public function isObject(): bool;

    public function isComposite(): bool;

    public function isUnknown(): bool;
}
