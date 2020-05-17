<?php

declare(strict_types=1);

namespace kuiper\web\security;

class Acl implements AclInterface
{
    private $acl = [];

    public function allow($role, $resource, $action)
    {
        $this->acl[$role][$resource][$action] = true;
    }

    public function isAllowed($role, $resource, $action): bool
    {
        return !empty($this->acl[$role][$resource][$action]);
    }
}
