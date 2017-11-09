<?php

namespace kuiper\web\session;

interface SessionInterface
{
    /**
     * Starts session, optionally using an adapter.
     */
    public function start();

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
     *
     * @return bool
     */
    public function has($index);

    /**
     * Removes a session variable from an application context.
     *
     * @param string $index
     */
    public function remove($index);

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
    public function destroy($removeData = false);

    /**
     * Regenerate session's id.
     *
     * @param bool $deleteOldSession
     */
    public function regenerateId($deleteOldSession = true);
}
