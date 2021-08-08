<?php

declare(strict_types=1);

namespace kuiper\di;

interface DefinitionConfiguration extends ContainerBuilderAwareInterface
{
    public const HIGH_PRIORITY = 128;
    public const LOW_PRIORITY = 1024;

    public function getDefinitions(): array;
}
