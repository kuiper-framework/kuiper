<?php
namespace kuiper\web\security;

class Acl implements AclInterface
{
    private $acl = [];
    
    public function allow($role, $resource, $action)
    {
        $this->acl[$role][$resource][$action] = true;
    }

    public function isAllowed($role, $resource, $action)
    {
        return !empty($this->acl[$role][$resource][$action]);
    }
}
