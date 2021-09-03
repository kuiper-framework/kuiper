<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use function DI\factory;
use kuiper\di\ContainerBuilderAwareInterface;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\helper\PropertyResolverInterface;
use kuiper\serializer\NormalizerInterface;
use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ConfigurationProperties implements ComponentInterface, ContainerBuilderAwareInterface
{
    use ComponentTrait;
    use ContainerBuilderAwareTrait;

    /**
     * @var string
     */
    public $prefix;

    public function handle(): void
    {
        $this->containerBuilder->addDefinitions([
            $this->getTargetClass() => factory(function (ContainerInterface $container, NormalizerInterface $serializer) {
                if (!$container->has(PropertyResolverInterface::class)) {
                    throw new \InvalidArgumentException(PropertyResolverInterface::class.' should be registered in container');
                }
                $value = $container->get(PropertyResolverInterface::class)->get($this->prefix);

                return $serializer->denormalize($value, $this->getTargetClass());
            }),
        ]);
    }
}
