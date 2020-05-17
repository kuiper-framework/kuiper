<?php

declare(strict_types=1);

namespace kuiper\web\session;

use Psr\Http\Message\ResponseInterface;

/**
 * @SuppressWarnings("Globals")
 */
class PhpSession implements SessionInterface
{
    use SessionTrait;

    public function __construct(bool $autoStart)
    {
        $this->autoStart = $autoStart;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        if (!$this->started && !headers_sent() && PHP_SESSION_ACTIVE !== session_status()) {
            session_start();
            $this->started = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($index, $defaultValue = null)
    {
        $this->checkStart();

        return $_SESSION[$index] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($index, $value)
    {
        $this->checkStart();
        $_SESSION[$index] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function has($index): bool
    {
        $this->checkStart();

        return isset($_SESSION[$index]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($index): void
    {
        $this->checkStart();
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
    public function destroy($removeData = false): bool
    {
        $this->checkStart();
        if ($removeData) {
            $_SESSION = [];
        }
        $this->started = false;

        return session_destroy();
    }

    /**
     * {@inheritdoc}
     */
    public function regenerateId($deleteOldSession = true): void
    {
        session_regenerate_id($deleteOldSession);
    }

    public function current()
    {
        $this->checkStart();

        return current($_SESSION);
    }

    public function next()
    {
        $this->checkStart();

        return next($_SESSION);
    }

    public function key()
    {
        $this->checkStart();

        return key($_SESSION);
    }

    public function valid(): bool
    {
        $this->checkStart();

        return null !== key($_SESSION);
    }

    public function rewind()
    {
        $this->checkStart();

        return reset($_SESSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
