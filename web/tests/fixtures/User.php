<?php

declare(strict_types=1);

namespace kuiper\web\fixtures;

use kuiper\web\security\UserIdentity;

class User implements UserIdentity
{
    public function getUsername(): string
    {
        return 'user';
    }

    public function getAuthorities(): array
    {
        return [];
    }
}
