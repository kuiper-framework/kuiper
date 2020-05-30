<?php

declare(strict_types=1);

namespace kuiper\db\converter;

class AttributeConverterRegistry
{
    /**
     * @var AttributeConverterInterface[]
     */
    private $converters;

    public function get(string $value): ?AttributeConverterInterface
    {
        return $this->converters[$value] ?? null;
    }

    public function register(string $name, AttributeConverterInterface $converter): void
    {
        $this->converters[$name] = $converter;
    }
}
