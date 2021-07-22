<?php

declare(strict_types=1);

namespace kuiper\tars\type;

class VoidType extends AbstractType
{
    /**
     * @var VoidType
     */
    private static $INSTANCE;

    public static function instance(): self
    {
        if (null === self::$INSTANCE) {
            self::$INSTANCE = new self();
        }

        return self::$INSTANCE;
    }

    public function isVoid(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return 'void';
    }

    public function getTarsType(): int
    {
        throw new \BadMethodCallException('cannot cast void to tars type');
    }
}
