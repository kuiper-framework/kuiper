<?php

namespace kuiper\web\security;

use kuiper\web\session\SessionInterface;

class Auth implements AuthInterface
{
    /**
     * 为降低用户在页面使用（ajax 调用）时出现过期情况，加入 regenerate_after 设置
     * regenerate_after 比实际过期时间要提前 300 秒（如果 lifetime/5 小于300s，则使用 lifetime/5）
     * 当用户进入页面时，如果到了 regenerate_after 时间，则需要重新登录.
     */
    const REGENERATE_AFTER = '__gc_time';

    /**
     * session 数据中记录 auth 信息 key 值
     */
    private $sessionKey;

    /**
     * session 数据.
     */
    private $sessionData;

    /**
     * session 组件.
     */
    private $session;

    /**
     * 是否需要重新生成 session.
     */
    private $needRegenerate = false;

    /**
     * @var int
     */
    private $lifetime;

    public function __construct(SessionInterface $session, $sessionKey = 'auth:id', $lifetime = null)
    {
        $this->session = $session;
        $this->sessionKey = $sessionKey;
        $this->lifetime = $lifetime;
    }

    public function reset()
    {
        $this->sessionData = null;
    }

    public function initialize()
    {
        if (isset($this->sessionData)) {
            return;
        }
        $this->sessionData = $this->session->get($this->sessionKey);
        if (isset($this->sessionData)) {
            $now = time();
            $discardTime = isset($this->sessionData[self::REGENERATE_AFTER])
                ? $this->sessionData[self::REGENERATE_AFTER]
                : $now;
            $this->needRegenerate = ($now >= $discardTime);
        } else {
            $this->sessionData = false;
        }
    }

    public function getSessionKey()
    {
        return $this->sessionKey;
    }

    public function offsetExists($offset)
    {
        $this->initialize();

        return isset($this->sessionData[$offset]);
    }

    public function offsetGet($offset)
    {
        $this->initialize();
        if (isset($this->sessionData[$offset])) {
            return $this->sessionData[$offset];
        } else {
            return null;
        }
    }

    public function offsetSet($offset, $value)
    {
        $this->initialize();
        $this->sessionData[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        $this->initialize();
        unset($this->sessionData[$offset]);
    }

    public function __get($name)
    {
        $this->initialize();
        if (isset($this->sessionData[$name])) {
            return $this->sessionData[$name];
        } else {
            return null;
        }
    }

    public function __set($name, $value)
    {
        $this->initialize();
        if (isset($this->sessionData[$name])) {
            $this->sessionData[$name] = $value;
        }
    }

    public function __isset($name)
    {
        $this->initialize();

        return isset($this->sessionData[$name]);
    }

    public function getIdentity()
    {
        $this->initialize();

        return $this->sessionData;
    }

    /**
     * 用户登录操作.
     *
     * @param mixed $identity 用户数据
     */
    public function login($identity)
    {
        $this->initialize();
        if ($this->needRegenerate) {
            $this->session->regenerateId(true);
        }
        foreach ($identity as $name => $val) {
            $this->sessionData[$name] = $val;
        }
        $lifetime = isset($this->lifetime) ? $this->lifetime : ini_get('session.cookie_lifetime');
        $this->sessionData[self::REGENERATE_AFTER] = time() + $lifetime - min($lifetime * 0.2, 300);
        $this->session->set($this->sessionKey, $this->sessionData);
    }

    /**
     * 用户注销操作.
     *
     * @param bool $destroySession
     */
    public function logout($destroySession = true)
    {
        $this->initialize();
        if ($destroySession) {
            $this->session->destroy();
        } else {
            $this->session->set($this->sessionKey, false);
        }
        $this->sessionData = false;
    }

    /**
     * 判断用户是否登录.
     */
    public function isGuest()
    {
        $this->initialize();

        return false === $this->sessionData;
    }

    /**
     * 判断用户是否需要重新登录.
     */
    public function isNeedLogin()
    {
        $this->initialize();

        return $this->isGuest() || $this->needRegenerate;
    }
}
