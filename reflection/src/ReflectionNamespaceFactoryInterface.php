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

namespace kuiper\reflection;

interface ReflectionNamespaceFactoryInterface
{
    /**
     * Creates ReflectionNamespaceInterface instance.
     *
     * @param string $namespace
     *
     * @return ReflectionNamespaceInterface
     */
    public function create($namespace): ReflectionNamespaceInterface;
}
