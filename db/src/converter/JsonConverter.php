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

namespace kuiper\db\converter;

use kuiper\db\metadata\Column;
use kuiper\db\metadata\ColumnInterface;

class JsonConverter implements AttributeConverterInterface
{
    public function __construct(private readonly bool $assoc = true,
                                private readonly int $options = (JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))
    {
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn(mixed $attribute, ColumnInterface $column): mixed
    {
        return json_encode($attribute, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute(mixed $dbData, ColumnInterface $column): mixed
    {
        return json_decode($dbData, $this->assoc);
    }
}
