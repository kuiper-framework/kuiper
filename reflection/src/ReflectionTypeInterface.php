<?php

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
