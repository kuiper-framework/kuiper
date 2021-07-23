<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Source\DefinitionSource;
use Psr\Container\ContainerInterface;

interface ContainerBuilderInterface
{
    /**
     * add definitions.
     *
     * @param array|string|DefinitionSource $definitions
     *
     * @return static
     */
    public function addDefinitions(...$definitions): ContainerBuilderInterface;

    /**
     * Add configuration object.
     *
     * @return static
     */
    public function addConfiguration(object $configuration): ContainerBuilderInterface;

    /**
     * Add callback when container is ready.
     *
     * @return static
     */
    public function defer(callable $callback): ContainerBuilderInterface;

    /**
     * @return static
     */
    public function addAwareInjection(AwareInjection $awareInjection): ContainerBuilderInterface;

    /**
     * Create container.
     */
    public function build(): ContainerInterface;
}
