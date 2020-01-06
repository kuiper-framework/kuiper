<?php

declare(strict_types=1);

namespace kuiper\di;

interface ComponentScannerInterface
{
    public function scan(array $namespaces): void;
}
