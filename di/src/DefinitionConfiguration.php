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

interface DefinitionConfiguration extends ContainerBuilderAwareInterface
{
    public const HIGH_PRIORITY = 128;
    public const LOW_PRIORITY = 1024;

    /**
     * Creates php-di definitions.
     *
     * @return array
     */
    public function getDefinitions(): array;
}
