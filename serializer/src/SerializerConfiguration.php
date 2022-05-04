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

namespace kuiper\serializer;

use DI\Attribute\Inject;
use kuiper\di\attribute\Bean;
use function DI\get;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Enum;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\serializer\normalizer\DateTimeNormalizer;
use kuiper\serializer\normalizer\EnumNormalizer;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use Psr\Container\ContainerInterface;

class SerializerConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            NormalizerInterface::class => get(Serializer::class),
            JsonSerializerInterface::class => get(Serializer::class),
        ];
    }

    #[Bean]
    public function serializer(ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory,
                               #[Inject("serializerNormalizers")] array $normalizers): Serializer
    {
        return new Serializer($reflectionDocBlockFactory, $normalizers);
    }

    #[Bean("serializerNormalizers")]
    public function serializerNormalizers(ContainerInterface $container): array
    {
        return [
            \DateTimeInterface::class => $container->get(DateTimeNormalizer::class),
            Enum::class => $container->get(EnumNormalizer::class),
            \Exception::class => $container->get(ExceptionNormalizer::class),
        ];
    }
}
