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
    public function defer(callable $callback, int $priority = null): ContainerBuilderInterface;

    /**
     * @return static
     */
    public function addAwareInjection(AwareInjection $awareInjection): ContainerBuilderInterface;

    /**
     * Create container.
     */
    public function build(): ContainerInterface;
}
