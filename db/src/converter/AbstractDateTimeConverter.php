<?php

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
     *
     * @param \DateTimeInterface|string $attribute
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
