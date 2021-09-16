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
use kuiper\db\DateTimeFactoryInterface;
use kuiper\db\metadata\Column;

class AbstractDateTimeConverter implements AttributeConverterInterface
{
    /**
     * @var DateTimeFactoryInterface
     */
    private $dateTimeFactory;

    public function __construct(DateTimeFactoryInterface $dateTimeFactory)
    {
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn($attribute, Column $column)
    {
        if ($attribute instanceof DateTimeInterface) {
            return $this->format($attribute);
        }

        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, Column $column)
    {
        try {
            return $this->dateTimeFactory->stringToTime($dbData);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function format(DateTimeInterface $attribute): string
    {
        return $this->dateTimeFactory->timeToString($attribute);
    }
}
