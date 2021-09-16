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

namespace kuiper\rpc\server;

interface ServiceRegistryInterface
{
    /**
     * @param Service $service
     */
    public function register(Service $service): void;

    /**
     * @param Service $service
     */
    public function deregister(Service $service): void;
}
