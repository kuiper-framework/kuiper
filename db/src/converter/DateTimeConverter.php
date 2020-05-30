<?php

declare(strict_types=1);

namespace kuiper\db\converter;

use kuiper\db\DateTimeFactoryInterface;
use kuiper\db\metadata\Column;

class DateTimeConverter implements AttributeConverterInterface
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
        return $this->dateTimeFactory->timeToString($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, Column $column)
    {
        return $this->dateTimeFactory->stringToTime($dbData);
    }
}
