<?php

declare(strict_types=1);

namespace kuiper\web\security;

class PermissionEvaluator
{
    /**
     * @var string
     */
    private static $SUPER_USER_ROLE = 'admin';

    /**
     * @var AclInterface
     */
    private $acl;

    /**
     * PermissionEvaluator constructor.
     *
     * @param AclInterface $acl
     */
    public function __construct(AclInterface $acl)
    {
        $this->acl = $acl;
    }

    /**
     * @param UserIdentity    $userIdentity
     * @param string|string[] $authorities
     *
     * @return bool
     */
    public function hasPermission(UserIdentity $userIdentity, $authorities): bool
    {
        return $this->isAllowAll($userIdentity->getAuthorities(), (array) $authorities);
    }

    /**
     * @param UserIdentity $userIdentity
     * @param string[]     $authorities
     *
     * @return bool
     */
    public function hasAnyPermission(UserIdentity $userIdentity, array $authorities): bool
    {
        return $this->isAllowAny($userIdentity->getAuthorities(), $authorities);
    }

    /**
     * The super user role name.
     */
    public static function setSuperUserRole(string $roleName): void
    {
        self::$SUPER_USER_ROLE = $roleName;
    }

    protected function isSuperUser(array $roles): bool
    {
        return in_array(self::$SUPER_USER_ROLE, $roles, true);
    }

    private function isAllowAll(array $roles, array $authorities): bool
    {
        if (empty($authorities) || $this->isSuperUser($roles)) {
            return true;
        }

        if (empty($roles)) {
            return false;
        }

        foreach ($authorities as $authority) {
            $allow = false;
            foreach ($roles as $role) {
                if ($this->acl->isAllowed($role, $authority)) {
                    $allow = true;
                    break;
                }
            }
            if (!$allow) {
                return false;
            }
        }

        return true;
    }

    private function isAllowAny(array $roles, array $authorities): bool
    {
        if (empty($authorities) || $this->isSuperUser($roles)) {
            return true;
        }

        if (empty($roles)) {
            return false;
        }

        foreach ($authorities as $authority) {
            foreach ($roles as $role) {
                if ($this->acl->isAllowed($role, $authority)) {
                    return true;
                }
            }
        }

        return false;
    }
}
