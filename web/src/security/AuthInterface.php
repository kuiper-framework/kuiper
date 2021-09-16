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
