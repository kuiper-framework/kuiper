<?php
namespace kuiper\web\session;

class Session implements SessionInterface
{
    /**
     * @var boolean
     */
    private $started = false;

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function get($index, $defaultValue = null)
    {
        return isset($_SESSION[$index]) ? $_SESSION[$index] : null;
    }

    /**
     * @inheritDoc
     */
    public function set($index, $value)
    {
        $_SESSION[$index] = $value;
    }

    /**
     * @inheritDoc
     */
    public function has($index)
    {
        return isset($_SESSION[$index]);
    }

    /**
     * @inheritDoc
     */
    public function remove($index)
    {
        unset($_SESSION[$index]);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * @inheritDoc
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
}
