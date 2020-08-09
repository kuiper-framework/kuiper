<?php

declare(strict_types=1);

namespace kuiper\web\security;

interface AclInterface
{
    /**
     * Check whether a role is allowed to access the resource.
     */
    public function isAllowed(string $role, string $resource): bool;
}
