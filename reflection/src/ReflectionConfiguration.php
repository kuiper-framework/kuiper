<?php

declare(strict_types=1);

namespace kuiper\reflection;

use function DI\factory;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

class ReflectionConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            ReflectionDocBlockFactoryInterface::class => factory([ReflectionDocBlockFactory::class, 'getInstance']),
            ReflectionFileFactoryInterface::class => factory([ReflectionFileFactory::class, 'getInstance']),
            ReflectionNamespaceFactoryInterface::class => factory([ReflectionNamespaceFactory::class, 'getInstance']),
        ];
    }
}
