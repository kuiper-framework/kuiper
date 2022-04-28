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
     */
    public function addDefinitions(array|string|DefinitionSource ...$definitions): ContainerBuilderInterface;

    /**
     * Add configuration object.
     */
    public function addConfiguration(object $configuration): ContainerBuilderInterface;

    /**
     * Remove configuration.
     */
    public function removeConfiguration(string|object $configuration): ContainerBuilderInterface;

    /**
     * Change configuration load order.
     *
     * @param int[] $priorities
     */
    public function setConfigurationPriorities(array $priorities): ContainerBuilderInterface;

    /**
     * Scan namespace.
     *
     * @param string[] $namespaces
     *
     * @return ContainerBuilderInterface
     */
    public function componentScan(array $namespaces): ContainerBuilderInterface;

    /**
     * Exclude namespace.
     *
     * @param string $namespace
     */
    public function componentScanExclude(string $namespace): ContainerBuilderInterface;

    /**
     * Add callback when container is ready.
     */
    public function defer(callable $callback, int $priority = null): ContainerBuilderInterface;

    /**
     * Add aware injection.
     */
    public function addAwareInjection(AwareInjection $awareInjection): ContainerBuilderInterface;

    /**
     * Create container.
     */
    public function build(): ContainerInterface;
}
