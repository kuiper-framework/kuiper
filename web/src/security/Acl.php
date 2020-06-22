<?php

declare(strict_types=1);

namespace kuiper\web\security;

class Acl implements AclInterface
{
    private $allows = [];

    public function allow(string $role, string $rule): void
    {
        $this->allows[$role][$rule] = true;
    }

    public function isAllowed(string $role, string $authority): bool
    {
        if (isset($this->allows[$role][$authority])) {
            return true;
        }
        foreach ($this->allows[$role] as $rule) {
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
