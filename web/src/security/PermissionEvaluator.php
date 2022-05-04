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

namespace kuiper\web\security;

class PermissionEvaluator
{
    private static string $SUPER_USER_ROLE = 'admin';

    /**
     * PermissionEvaluator constructor.
     */
    public function __construct(private readonly AclInterface $acl)
    {
    }

    /**
     * @param UserIdentity    $userIdentity
     * @param string|string[] $authorities
     *
     * @return bool
     */
    public function hasPermission(UserIdentity $userIdentity, array|string $authorities): bool
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
