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

class JoinerConverter implements AttributeConverterInterface
{
    public function __construct(private readonly string $delimiter = '|', private readonly bool $around = false)
    {
    }

    public function convertToDatabaseColumn(mixed $attribute, Column $column): mixed
    {
        if (!is_array($attribute)) {
            throw new \InvalidArgumentException('attribute should be array');
        }
        $value = implode($this->delimiter, $attribute);

        return $this->around ? $this->delimiter.$value.$this->delimiter : $value;
    }

    public function convertToEntityAttribute(mixed $dbData, Column $column): mixed
    {
        $trim = trim($dbData, $this->delimiter);
        if (empty($trim)) {
            return [];
        }

        return explode($this->delimiter, $trim);
    }
}
