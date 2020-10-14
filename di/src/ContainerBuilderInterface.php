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
    public function addDefinitions(...$definitions);

    /**
     * Add configuration object.
     *
     * @param object $configuration
     *
     * @return static
     */
    public function addConfiguration($configuration);

    /**
     * Add callback when container is ready.
     *
     * @return static
     */
    public function defer(callable $callback);

    /**
     * @return static
     */
    public function addAwareInjection(AwareInjection $awareInjection);

    /**
     * Create container.
     */
    public function build(): ContainerInterface;
}
