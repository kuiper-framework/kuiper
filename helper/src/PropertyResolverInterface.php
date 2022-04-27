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

namespace kuiper\helper;

interface PropertyResolverInterface
{
    /**
     * Finds an entry.
     *
     * @param string $key     identifier of the entry to look for
     * @param mixed  $default the default value
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Returns true if the entry exists.
     * Returns false otherwise.
     *
     * @param string $key identifier of the entry to look for
     */
    public function has(string $key): bool;
}
