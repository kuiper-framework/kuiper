<?php

namespace kuiper\web\session;

/**
 * @SuppressWarnings("Globals")
 */
class Session implements SessionInterface
{
    /**
     * @var bool
     */
    private $started = false;

    public function __construct(array $options = [])
    {
        if (isset($options['cookie_lifetime'])) {
            ini_set('session.cookie_lifetime', $options['cookie_lifetime']);
        }
        if (isset($options['cookie_name'])) {
            ini_set('session.name', $options['cookie_name']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if (!headers_sent() && !$this->started && session_status() != PHP_SESSION_ACTIVE) {
            session_start();
            $this->started = true;

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get($index, $defaultValue = null)
    {
        return isset($_SESSION[$index]) ? $_SESSION[$index] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($index, $value)
    {
        $_SESSION[$index] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function has($index)
    {
        return isset($_SESSION[$index]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($index)
    {
        unset($_SESSION[$index]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return session_id();
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
    public function destroy($removeData = false)
    {
        if ($removeData) {
            $_SESSION = [];
        }
        $this->started = false;

        return session_destroy();
    }

    /**
     * {@inheritdoc}
     */
    public function regenerateId($deleteOldSession = true)
    {
        session_regenerate_id($deleteOldSession);
    }

    public function __get($index)
    {
        return $this->get($index);
    }

    public function __set($index, $value)
    {
        $this->set($index, $value);
    }

    public function __isset($index)
    {
        return $this->has($index);
    }

    public function __unset($index)
    {
        $this->remove($index);
    }
}
