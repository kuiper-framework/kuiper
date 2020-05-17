<?php

declare(strict_types=1);

namespace kuiper\web\session;

use Psr\Http\Message\ResponseInterface;

interface SessionInterface extends \ArrayAccess, \Iterator
{
    /**
     * Starts session, optionally using an adapter.
     */
    public function start(): void;

    /**
     * Gets a session variable from an application context.
     *
     * @param string $index
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($index, $defaultValue = null);

    /**
     * Sets a session variable in an application context.
     *
     * @param string $index
     * @param mixed  $value
     */
    public function set($index, $value);

    /**
     * Check whether a session variable is set in an application context.
     *
     * @param string $index
     */
    public function has($index): bool;

    /**
     * Removes a session variable from an application context.
     *
     * @param string $index
     */
    public function remove($index): void;

    /**
     * Returns active session id.
     */
    public function getId(): string;

    /**
     * Check whether the session has been started.
     */
    public function isStarted(): bool;

    /**
     * Destroys the active session.
     *
     * @param bool $removeData
     */
    public function destroy($removeData = false): bool;

    /**
     * Regenerate session's id.
     *
     * @param bool $deleteOldSession
     */
    public function regenerateId($deleteOldSession = true): void;

    public function setCookie(ResponseInterface $response): ResponseInterface;
}
