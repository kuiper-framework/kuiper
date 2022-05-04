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

use kuiper\web\exception\UnauthorizedException;
use kuiper\web\session\SessionInterface;

class Auth implements AuthInterface
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly string $sessionKey = 'auth')
    {
    }

    public function getSessionKey(): string
    {
        return $this->sessionKey;
    }

    public function getIdentity(): UserIdentity
    {
        if (!$this->session->has($this->sessionKey)) {
            throw new UnauthorizedException('Current user was not authorized');
        }

        return $this->session->get($this->sessionKey);
    }

    /**
     * 用户登录操作.
     *
     * {@inheritdoc}
     */
    public function login(UserIdentity $identity): void
    {
        $this->session->set($this->sessionKey, $identity);
    }

    /**
     * 用户注销操作.
     */
    public function logout(bool $destroySession = true): void
    {
        if ($destroySession) {
            $this->session->destroy();
        } else {
            $this->session->set($this->sessionKey, null);
        }
    }

    /**
     * 判断用户是否登录.
     */
    public function isGuest(): bool
    {
        return !$this->session->has($this->sessionKey);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized(): bool
    {
        return $this->session->has($this->sessionKey);
    }
}
