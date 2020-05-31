<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

interface ColumnInterface
{
    public function getName(): string;

    public function getPropertyPath(): string;

    public function getProperty(): MetaModelProperty;
}
