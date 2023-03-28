<?php

declare(strict_types=1);

namespace kuiper\db\attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Enumerated implements Attribute
{
    public const ORDINAL = 'ORDINAL';
    public const STRING = 'STRING';

    public function __construct(public readonly string $value = self::ORDINAL)
    {
    }
}
