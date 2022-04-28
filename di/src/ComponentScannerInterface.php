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

interface ComponentScannerInterface
{
    /**
     * Scan components.
     *
     * @param string[] $namespaces
     */
    public function scan(array $namespaces): void;

    /**
     * Exclude namespace for scan.
     *
     * @param string $namespace
     */
    public function exclude(string $namespace): void;
}
