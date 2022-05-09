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

namespace kuiper\web\session;

use kuiper\web\http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CacheStoreSession implements SessionInterface
{
    use SessionTrait;

    private ?string $sessionId = null;
    private array $sessionData = [];

    public function __construct(
        private readonly \SessionHandlerInterface $sessionHandler,
        private readonly ServerRequestInterface $request,
        private readonly string $cookieName,
        private readonly int $cookieLifetime,
        private readonly bool $compatibleMode,
        bool $autoStart)
    {
        $this->autoStart = $autoStart;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        if ($this->started) {
            return;
        }
        $cookies = $this->request->getCookieParams();
        if (isset($cookies[$this->cookieName]) && $this->validateSessionId($cookies[$this->cookieName])) {
            $this->sessionId = $cookies[$this->cookieName];
            $this->sessionData = $this->decode($this->sessionHandler->read($this->sessionId));
        } else {
            $this->sessionId = null;
            $this->sessionData = [];
        }

        $this->started = true;
    }

    /**
     * {@inheritdoc}
     */
    public function regenerateId(bool $deleteOldSession = true): void
    {
        if ($deleteOldSession) {
            if (null !== $this->sessionId) {
                $this->sessionHandler->destroy($this->sessionId);
            }
            $this->sessionData = [];
        }
        $this->sessionId = null;
    }

    /**
     * {@inheritdoc}
     */
    public function get($index, $defaultValue = null): mixed
    {
        $this->checkStart();

        return $this->sessionData[$index] ?? $defaultValue;
    }

    /**
     * {@inheritdoc}
     */
    public function set($index, $value): void
    {
        $this->checkStart();
        $this->sessionData[$index] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function has($index): bool
    {
        $this->checkStart();

        return isset($this->sessionData[$index]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($index): void
    {
        $this->checkStart();
        unset($this->sessionData[$index]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        $this->checkStart();
        if (null === $this->sessionId) {
            $this->sessionId = $this->sessionHandler->create_sid();
        }

        return $this->sessionId;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(bool $removeData = false): bool
    {
        if (null !== $this->sessionId) {
            $this->sessionHandler->destroy($this->sessionId);
        }
        if ($removeData) {
            $this->sessionData = [];
        }
        $this->started = false;
        $this->sessionId = null;

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function current()
    {
        $this->checkStart();

        return current($this->sessionData);
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->checkStart();

        next($this->sessionData);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function key()
    {
        $this->checkStart();

        return key($this->sessionData);
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        $this->checkStart();

        return null !== key($this->sessionData);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->checkStart();

        reset($this->sessionData);
    }

    protected function validateSessionId(string $sid): bool
    {
        return (bool) preg_match('/^[0-9a-zA-Z]+$/', $sid);
    }

    /**
     * @param string $data
     *
     * @return array|mixed
     */
    protected function decode($data)
    {
        if ($this->compatibleMode) {
            $_SESSION = [];
            session_decode($data);

            return $_SESSION;
        } else {
            return @unserialize($data) ?? [];
        }
    }

    /**
     * @param mixed $session
     */
    protected function encode($session): string
    {
        if ($this->compatibleMode) {
            $_SESSION = $session;

            return session_encode();
        } else {
            return serialize($session);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie(ResponseInterface $response): ResponseInterface
    {
        $cookies = ResponseHelper::parseSetCookieHeader($response->getHeader('set-cookie'));
        if ($this->isStarted()) {
            $sid = $this->getId();
            if (!empty($this->sessionData)) {
                $this->sessionHandler->write($sid, $this->encode($this->sessionData));
            }
            $attributes = ['Path' => ini_get('session.cookie_path')];
            $domain = ini_get('session.cookie_domain');
            if (!empty($domain)) {
                $attributes['Domain'] = $domain;
            }
            $httpOnly = ini_get('session.cookie_httponly');
            if ($httpOnly) {
                $attributes['HttpOnly'] = (bool) $httpOnly;
            }
            if ($this->cookieLifetime > 0) {
                $attributes['Expires'] = gmdate('D, d M Y H:i:s T', time() + $this->cookieLifetime);
            }
            $secure = ini_get('session.cookie_secure');
            if ($secure) {
                $attributes['Secure'] = (bool) $secure;
            }
            $cookies[$this->cookieName] = ResponseHelper::buildSetCookie($this->cookieName, $sid, $attributes);

            return ResponseHelper::setCookie($response, $cookies);
        }

        // not start, remove session cookie
        if (isset($cookies[$this->cookieName])) {
            unset($cookies[$this->cookieName]);

            return ResponseHelper::setCookie($response, $cookies);
        }

        return $response;
    }
}
