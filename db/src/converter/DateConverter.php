<?php

declare(strict_types=1);

namespace kuiper\db\converter;

use kuiper\db\DateTimeFactoryInterface;
use kuiper\db\metadata\Column;

class DateConverter implements AttributeConverterInterface
{
    private const DATE_FORMAT = 'Y-m-d';

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
     *
     * @param \DateTime $attribute
     */
    public function convertToDatabaseColumn($attribute, Column $column)
    {
        return $attribute->format(self::DATE_FORMAT);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, Column $column)
    {
        return $this->dateTimeFactory->stringToTime($dbData);
    }
}
