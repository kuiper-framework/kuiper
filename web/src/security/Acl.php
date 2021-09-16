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

class Acl implements AclInterface
{
    /**
     * @var array
     */
    private $allows = [];

    /**
     * Add resource to the role.
     *
     * @param string $authority the resource or the pattern to match the resource
     */
    public function allow(string $role, string $authority): void
    {
        $this->allows[$role][$authority] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed(string $role, string $authority): bool
    {
        if (!isset($this->allows[$role])) {
            return $this->matches($role, $authority);
        }
        if (isset($this->allows[$role][$authority])) {
            return true;
        }
        foreach ($this->allows[$role] as $rule => $allow) {
            if ($this->matches($rule, $authority)) {
                return true;
            }
        }

        return false;
    }

    private function matches(string $rule, string $authority): bool
    {
        return fnmatch($rule, $authority);
    }
}
