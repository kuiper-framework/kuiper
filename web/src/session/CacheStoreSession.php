<?php

declare(strict_types=1);

namespace kuiper\web\session;

use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\SetCookies;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CacheStoreSession implements SessionInterface
{
    use SessionTrait;

    /**
     * @var \SessionHandlerInterface|\SessionIdInterface
     */
    private $sessionHandler;
    /**
     * @var int
     */
    private $cookieLifetime;
    /**
     * @var string
     */
    private $cookieName;
    /**
     * @var bool
     */
    private $compatibleMode;
    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var string|null
     */
    private $sessionId;

    /**
     * @var array
     */
    private $sessionData;

    public function __construct(\SessionHandlerInterface $handler, ServerRequestInterface $request, string $cookieName, int $cookieLifetime, bool $compatibleMode, bool $autoStart)
    {
        $this->sessionHandler = $handler;
        $this->cookieName = $cookieName;
        $this->cookieLifetime = $cookieLifetime;
        $this->compatibleMode = $compatibleMode;
        $this->autoStart = $autoStart;
        $this->request = $request;
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
    public function regenerateId($deleteOldSession = true): void
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
    public function get($index, $defaultValue = null)
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
    public function destroy($remove = false): bool
    {
        if (null !== $this->sessionId) {
            $this->sessionHandler->destroy($this->sessionId);
        }
        if ($remove) {
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
        $cookies = SetCookies::fromResponse($response);
        if ($this->isStarted()) {
            $sid = $this->getId();
            if (!empty($this->sessionData)) {
                $this->sessionHandler->write($sid, $this->encode($this->sessionData));
            }
            $cookie = SetCookie::create($this->cookieName, $sid)
                ->withPath(ini_get('session.cookie_path'));
            $domain = ini_get('session.cookie_domain');
            if (!empty($domain)) {
                $cookie = $cookie->withDomain($domain);
            }
            $httpOnly = ini_get('session.cookie_httponly');
            if ($httpOnly) {
                $cookie = $cookie->withHttpOnly((bool) $httpOnly);
            }
            if ($this->cookieLifetime > 0) {
                $cookie = $cookie->withExpires(time() + $this->cookieLifetime);
            }
            $secure = ini_get('session.cookie_secure');
            if ($secure) {
                $cookie = $cookie->withSecure((bool) $secure);
            }

            return $cookies->with($cookie)
                ->renderIntoSetCookieHeader($response);
        }

        // not start, remove session cookie
        if ($cookies->has($this->cookieName)) {
            return $cookies->without($this->cookieName)
                ->renderIntoSetCookieHeader($response);
        }

        return $response;
    }
}
