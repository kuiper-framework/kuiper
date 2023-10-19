<?php

declare(strict_types=1);

namespace kuiper\metrics\export;

interface ExporterInterface
{
    public function export(): void;
}
