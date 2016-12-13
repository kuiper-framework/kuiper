<?php
namespace kuiper\web\session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Dflydev\FigCookies\SetCookies;
use Dflydev\FigCookies\SetCookie;
use SessionHandlerInterface;

class ManagedSession implements ManagedSessionInterface
{
    /**
     * @var boolean
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
     * @var SessionHandlerInterface
     */
    private $handler;

    public function __construct(SessionHandlerInterface $handler, array $options = [])
    {
        if (isset($options['cookie_lifetime'])) {
            ini_set('session.cookie_lifetime', $options['cookie_lifetime']);
        }
        if (isset($options['cookie_name'])) {
            ini_set('session.name', $options['cookie_name']);
        }
        $this->handler = $handler;
    }

    /**
     * @inheritdoc
     */
    public function start()
    {
        if ($this->started) {
            return false;
        }
        $name = $this->getCookieName();
        $cookies = $this->request->getCookieParams();
        if (isset($cookies[$name])) {
            $this->sessionId = $cookies[$name];
            $this->sessionData = $this->handler->read($this->sessionId);
        }
        return $this->started = true;
    }

    /**
     * @inheritdoc
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->started = false;
        $this->request = $request;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function respond(ResponseInterface $response)
    {
        $name = $this->getCookieName();
        $cookies = SetCookies::fromResponse($response);
        if ($this->started) {
            $this->handler->write($sid = $this->getId(), $this->sessionData);
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
     * @inheritdoc
     */
    public function regenerateId($deleteOldSession = true)
    {
        if ($deleteOldSession) {
            if ($this->sessionId !== null) {
                $this->handler->destroy($this->sessionId);
            }
            $this->sessionData = [];
        }
        $this->sessionId = null;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function get($index, $default = null)
    {
        return isset($this->sessionData[$index]) ? $this->sessionData[$index] : $default;
    }

    /**
     * @inheritdoc
     */
    public function set($index, $value)
    {
        $this->sessionData[$index] = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function has($index)
    {
        return isset($this->sessionData[$index]);
    }

    /**
	 * @inheritdoc
	 */
    public function remove($index)
    {
        unset($this->sessionData[$index]);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        if ($this->sessionId === null) {
            $this->sessionId = $this->handler->create_sid();
        }
        return $this->sessionId;
    }

    /**
     * @inheritdoc
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * @inheritdoc
     */
    public function destroy($remove = false)
    {
        if ($this->sessionId !== null) {
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
}