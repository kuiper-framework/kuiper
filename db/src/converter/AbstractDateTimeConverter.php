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

use DateTimeInterface;
use Exception;
use kuiper\db\DateTimeFactoryInterface;
use kuiper\db\metadata\ColumnInterface;

class AbstractDateTimeConverter implements AttributeConverterInterface
{
    public function __construct(private readonly DateTimeFactoryInterface $dateTimeFactory)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn(mixed $attribute, ColumnInterface $column): string
    {
        if ($attribute instanceof DateTimeInterface) {
            return $this->format($attribute);
        }

        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute(mixed $dbData, ColumnInterface $column): ?DateTimeInterface
    {
        try {
            return $this->dateTimeFactory->stringToTime($dbData);
        } catch (Exception $e) {
            return null;
        }
    }

    protected function format(DateTimeInterface $attribute): string
    {
        return $this->dateTimeFactory->timeToString($attribute);
    }
}
