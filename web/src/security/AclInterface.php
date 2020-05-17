<?php

declare(strict_types=1);

namespace kuiper\web\security;

interface AclInterface
{
    /**
     * Check whether a role is allowed to access an action from a resource.
     *
     * @param string $role
     * @param string $resource
     * @param string $action
     */
    public function isAllowed($role, $resource, $action): bool;
}
