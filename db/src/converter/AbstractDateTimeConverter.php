<?php

declare(strict_types=1);

namespace kuiper\db\converter;

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
     *
     * @param \DateTime|string $attribute
     */
    public function convertToDatabaseColumn($attribute, Column $column)
    {
        if ($attribute instanceof \DateTime) {
            return $this->format($attribute);
        }

        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, Column $column)
    {
        return $this->dateTimeFactory->stringToTime($dbData);
    }

    protected function format(\DateTime $attribute): string
    {
        return $this->dateTimeFactory->timeToString($attribute);
    }
}
