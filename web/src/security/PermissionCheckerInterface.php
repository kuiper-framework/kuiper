<?php

namespace kuiper\web\security;

interface PermissionCheckerInterface
{
    /**
     * @param string $permission
     *
     * @return bool
     */
    public function check($permission);
}
