<?php

declare(strict_types=1);

namespace kuiper\web\security;

interface UserIdentity
{
    /**
     * Returns the username used to authenticate the user.
     *
     * @return string the username
     */
    public function getUsername(): string;

    /**
     * Returns the authorities granted to the user.
     *
     * @return string[] the authorities
     */
    public function getAuthorities(): array;
}
