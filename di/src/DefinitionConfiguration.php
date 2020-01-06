<?php

declare(strict_types=1);

namespace kuiper\di;

interface DefinitionConfiguration extends ContainerBuilderAwareInterface
{
    public function getDefinitions(): array;
}
