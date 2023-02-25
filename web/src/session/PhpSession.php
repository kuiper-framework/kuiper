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
    public function get($index, $defaultValue = null): mixed
    {
        $this->checkStart();

        return $_SESSION[$index] ?? $defaultValue;
    }

    /**
     * {@inheritdoc}
     */
    public function set($index, $value): void
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
    public function destroy(bool $removeData = false): bool
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
    public function regenerateId(bool $deleteOldSession = true): void
    {
        session_regenerate_id($deleteOldSession);
    }

    /**
     * {@inheritDoc}
     *
     * @return mixed
     */
    public function current(): mixed
    {
        $this->checkStart();

        return current($_SESSION);
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        $this->checkStart();

        next($_SESSION);
    }

    /**
     * {@inheritDoc}
     *
     * @return string|int|null
     */
    public function key(): string|int|null
    {
        $this->checkStart();

        return key($_SESSION);
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        $this->checkStart();

        return null !== key($_SESSION);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->checkStart();

        reset($_SESSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
