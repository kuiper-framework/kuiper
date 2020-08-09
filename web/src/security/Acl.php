<?php

declare(strict_types=1);

namespace kuiper\web\security;

class Acl implements AclInterface
{
    private $allows = [];

    /**
     * Add resource to the role.
     *
     * @param string $rule the resource or the pattern to match the resource
     */
    public function allow(string $role, string $rule): void
    {
        $this->allows[$role][$rule] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed(string $role, string $resource): bool
    {
        if (isset($this->allows[$role][$resource])) {
            return true;
        }
        foreach ($this->allows[$role] as $rule) {
            if ($this->matches($rule, $resource)) {
                return true;
            }
        }

        return false;
    }

    private function matches(string $rule, string $resource): bool
    {
        return fnmatch($rule, $resource);
    }
}
