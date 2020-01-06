<?php

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

    public function build(): ContainerInterface;
}
