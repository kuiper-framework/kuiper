<?php

declare(strict_types=1);

namespace kuiper\web\security;

use kuiper\web\exception\UnauthorizedException;
use kuiper\web\session\SessionInterface;

class Auth implements AuthInterface
{
    /**
     * session 数据中记录 auth 信息 key 值
     *
     * @var string
     */
    private $sessionKey;

    /**
     * session 组件.
     *
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session, $sessionKey = 'auth')
    {
        $this->session = $session;
        $this->sessionKey = $sessionKey;
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
