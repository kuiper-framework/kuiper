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

namespace kuiper\di\attribute;

use Attribute;
use kuiper\di\Component;
use kuiper\di\ContainerBuilderAwareInterface;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\helper\PropertyResolverInterface;
use kuiper\serializer\NormalizerInterface;
use Psr\Container\ContainerInterface;
use function DI\factory;

#[Attribute(Attribute::TARGET_CLASS)]
class ConfigurationProperties implements Component, ContainerBuilderAwareInterface
{
    use ComponentTrait;
    use ContainerBuilderAwareTrait;

    public function __construct(private readonly string $prefix)
    {
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function handle(): void
    {
        $this->containerBuilder->addDefinitions([
            $this->getComponentId() => factory(function (ContainerInterface $container, NormalizerInterface $serializer) {
                if (!$container->has(PropertyResolverInterface::class)) {
                    throw new \InvalidArgumentException(PropertyResolverInterface::class.' should be registered in container');
                }
                $value = $container->get(PropertyResolverInterface::class)->get($this->prefix);

                return $serializer->denormalize($value, $this->getTargetClass());
            }),
        ]);
    }
}
