<?php

declare(strict_types=1);

namespace kuiper\serializer;

use DI\Annotation\Inject;
use function DI\autowire;
use function DI\get;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Enum;
use kuiper\reflection\ReflectionDocBlockFactory;
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
            ReflectionDocBlockFactoryInterface::class => autowire(ReflectionDocBlockFactory::class),
        ];
    }

    /**
     * @Bean()
     * @Inject({"normalizers": "serializerNormalizers"})
     */
    public function serializer(AnnotationReaderInterface $annotationReader, ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory, array $normalizers): Serializer
    {
        return new Serializer($annotationReader, $reflectionDocBlockFactory, $normalizers);
    }

    /**
     * @Bean("serializerNormalizers")
     */
    public function serializerNormalizers(ContainerInterface $container): array
    {
        return [
            \DateTimeInterface::class => $container->get(DateTimeNormalizer::class),
            Enum::class => $container->get(EnumNormalizer::class),
            \Exception::class => $container->get(ExceptionNormalizer::class),
        ];
    }
}
