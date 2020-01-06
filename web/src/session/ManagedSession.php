<?php

namespace kuiper\web\session;

use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\SetCookies;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @SuppressWarnings("Globals")
 */
class ManagedSession implements ManagedSessionInterface
{
    /**
     * @var bool
     */
    private $started;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $sessionData = [];

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var \SessionHandlerInterface
     */
    private $handler;

    /**
     * @var bool
     */
    private $compatibleMode;

    public function __construct(\SessionHandlerInterface $handler, array $options = [])
    {
        if (isset($options['cookie_lifetime'])) {
            ini_set('session.cookie_lifetime', $options['cookie_lifetime']);
        }
        if (isset($options['cookie_name'])) {
            ini_set('session.name', $options['cookie_name']);
        }
        $this->compatibleMode = !empty($options['compatible_mode']);
        if ($this->compatibleMode) {
            session_start(); // for session_encode/session_decode
        }
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if ($this->started) {
            return false;
        }
        $name = $this->getCookieName();
        $cookies = $this->request->getCookieParams();
        if (isset($cookies[$name]) && $this->validateSessionId($cookies[$name])) {
            $this->sessionId = $cookies[$name];
            $this->sessionData = $this->decode($this->handler->read($this->sessionId));
        } else {
            $this->sessionId = null;
            $this->sessionData = [];
        }

        return $this->started = true;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->started = false;
        $this->request = $request;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function respond(ResponseInterface $response)
    {
        $name = $this->getCookieName();
        $cookies = SetCookies::fromResponse($response);
        if ($this->started) {
            $sid = $this->getId();
            if ($this->sessionData) {
                $this->handler->write($sid, $this->encode($this->sessionData));
            }
            $cookie = SetCookie::create($name, $sid)
                    ->withPath(ini_get('session.cookie_path'));
            $domain = ini_get('session.cookie_domain');
            if (!empty($domain)) {
                $cookie = $cookie->withDomain($domain);
            }
            $httpOnly = ini_get('session.cookie_httponly');
            if ($httpOnly) {
                $cookie = $cookie->withHttpOnly($httpOnly);
            }
            $lifetime = ini_get('session.cookie_lifetime');
            if ($lifetime > 0) {
                $cookie = $cookie->withExpires(time() + $lifetime);
            }
            $secure = ini_get('session.cookie_secure');
            if ($secure) {
                $cookie = $cookie->withSecure($secure);
            }

            return $cookies->with($cookie)
                ->renderIntoSetCookieHeader($response);
        } else {
            // not start, remove session cookie
            if ($cookies->has($name)) {
                return $cookies->without($name)
                    ->renderIntoSetCookieHeader($response);
            } else {
                return $response;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function regenerateId($deleteOldSession = true)
    {
        if ($deleteOldSession) {
            if (null !== $this->sessionId) {
                $this->handler->destroy($this->sessionId);
            }
            $this->sessionData = [];
        }
        $this->sessionId = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($index, $default = null)
    {
        return isset($this->sessionData[$index]) ? $this->sessionData[$index] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($index, $value)
    {
        $this->sessionData[$index] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has($index)
    {
        return isset($this->sessionData[$index]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($index)
    {
        unset($this->sessionData[$index]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        if (null === $this->sessionId) {
            $this->sessionId = $this->handler->create_sid();
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
    public function destroy($remove = false)
    {
        if (null !== $this->sessionId) {
            $this->handler->destroy($this->sessionId);
        }
        if ($remove) {
            $this->sessionData = [];
        }
        $this->started = false;
        $this->sessionId = null;

        return true;
    }

    public function __get($index)
    {
        return $this->get($index);
    }

    public function __set($index, $value)
    {
        return $this->set($index, $value);
    }

    public function __isset($index)
    {
        return $this->has($index);
    }

    public function __unset($index)
    {
        return $this->remove($index);
    }

    protected function getCookieName()
    {
        return ini_get('session.name');
    }

    protected function validateSessionId($sid)
    {
        return preg_match('/^[0-9a-zA-Z]+$/', $sid);
    }

    protected function decode($data)
    {
        if ($this->compatibleMode) {
            $_SESSION = [];
            session_decode($data);

            return $_SESSION;
        } else {
            return @unserialize($data) ?: [];
        }
    }

    protected function encode($session)
    {
        if ($this->compatibleMode) {
            $_SESSION = $session;

            return session_encode();
        } else {
            return serialize($session);
        }
    }
}
