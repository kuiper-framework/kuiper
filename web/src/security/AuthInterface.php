<?php

declare(strict_types=1);

namespace kuiper\web\security;

interface AuthInterface
{
    /**
     * make current user logged in.
     *
     * @param UserIdentity $identity the user identity info
     */
    public function login(UserIdentity $identity): void;

    /**
     * Gets the user identity.
     */
    public function getIdentity(): UserIdentity;

    /**
     * make current user logged out.
     *
     * @param bool $destroySession trigger destroy session
     */
    public function logout(bool $destroySession = true): void;

    /**
     * whether current user is logged in.
     */
    public function isGuest(): bool;

    /**
     * whether current user is logged in.
     */
    public function isAuthorized(): bool;
}
