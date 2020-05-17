<?php

declare(strict_types=1);

namespace kuiper\web\security;

interface AuthInterface extends \ArrayAccess
{
    /**
     * make current user logged in.
     *
     * @param array $identity the user identity info
     */
    public function login(array $identity): void;

    public function getIdentity(): array;

    /**
     * make current user logged out.
     *
     * @param bool $destroySession trigger destroy session
     */
    public function logout($destroySession = true): void;

    /**
     * whether current user is logged in.
     */
    public function isGuest(): bool;

    /**
     * whether current user is required to login.
     */
    public function isNeedLogin(): bool;
}
