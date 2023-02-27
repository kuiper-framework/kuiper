<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\reflection;

use kuiper\swoole\attribute\BootstrapConfiguration;
use function DI\factory;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

#[BootstrapConfiguration]
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
