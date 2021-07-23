<?php

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
     * @return mixed|null
     */
    public function get(string $key, $default = null);

    /**
     * Returns true if the entry exists.
     * Returns false otherwise.
     *
     * @param string $key identifier of the entry to look for
     */
    public function has(string $key): bool;
}
