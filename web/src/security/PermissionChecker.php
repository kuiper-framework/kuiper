<?php

namespace kuiper\web\security;

use InvalidArgumentException;

class PermissionChecker implements PermissionCheckerInterface
{
    /**
     * @var AclInterface
     */
    private $acl;

    /**
     * @var AuthInterface
     */
    private $auth;

    /**
     * @var string
     */
    private $superUserRole;

    public function __construct(AclInterface $acl, AuthInterface $auth, $superUserRole = 'Admin')
    {
        $this->acl = $acl;
        $this->auth = $auth;
        $this->superUserRole = $superUserRole;
    }

    /**
     * {@inheritdoc}
     */
    public function check($permission)
    {
        if ($this->auth->isGuest()) {
            return false;
        }

        $roles = $this->getRoles();
        if (empty($roles)) {
            return false;
        }
        if ($this->isSuperUser($roles)) {
            return true;
        }
        if (strpos($permission, ':') === false) {
            throw new InvalidArgumentException("Acl resource should in format 'resource:action'");
        }
        list($resource, $action) = explode(':', $permission);
        foreach ($roles as $role) {
            if ($this->acl->isAllowed($role, $resource, $action)) {
                return true;
            }
        }

        return false;
    }

    protected function getRoles()
    {
        return $this->auth->roles;
    }

    protected function isSuperUser($roles)
    {
        return in_array($this->superUserRole, $roles);
    }

    public function getAcl()
    {
        return $this->acl;
    }

    public function setAcl(AclInterface $acl)
    {
        $this->acl = $acl;

        return $this;
    }

    public function getAuth()
    {
        return $this->auth;
    }

    public function setAuth(AuthInterface $auth)
    {
        $this->auth = $auth;

        return $this;
    }

    public function getSuperUserRole()
    {
        return $this->superUserRole;
    }

    public function setSuperUserRole($superUserRole)
    {
        $this->superUserRole = $superUserRole;

        return $this;
    }
}
