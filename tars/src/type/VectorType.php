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

namespace kuiper\tars\type;

class VectorType extends AbstractType
{
    /**
     * @var Type
     */
    private $subType;

    /**
     * VectorType constructor.
     */
    public function __construct(Type $subType)
    {
        $this->subType = $subType;
    }

    public function getSubType(): Type
    {
        return $this->subType;
    }

    public function isVector(): bool
    {
        return true;
    }

    public function asVectorType(): VectorType
    {
        return $this;
    }

    public function getTarsType(): int
    {
        return Type::VECTOR;
    }

    public function __toString(): string
    {
        return sprintf('vector<%s>', (string) $this->subType);
    }

    public static function byteVector(): self
    {
        static $byteVectorType;
        if (null === $byteVectorType) {
            $byteVectorType = new VectorType(PrimitiveType::char());
        }

        return $byteVectorType;
    }
}
