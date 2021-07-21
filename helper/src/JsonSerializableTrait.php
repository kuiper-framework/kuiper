<?php

declare(strict_types=1);

namespace kuiper\helper;

trait JsonSerializableTrait
{
    protected function internalToArray(): array
    {
        $arr = [];
        foreach (get_object_vars($this) as $propertyName => $value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }
            $arr[$this->formatPropertyName($propertyName)] = $value;
        }

        return $arr;
    }

    protected function formatPropertyName(string $propertyName): string
    {
        return $propertyName;
    }

    public function toArray(): array
    {
        return $this->internalToArray();
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
