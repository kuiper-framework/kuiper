<?php
namespace kuiper\web\session;

interface SessionInterface
{
    /**
     * Starts session, optionally using an adapter
     */
    public function start();

    /**
     * Gets a session variable from an application context
     */
    public function get($index, $defaultValue = null);

    /**
     * Sets a session variable in an application context
     */
    public function set($index, $value);

    /**
     * Check whether a session variable is set in an application context
     */
    public function has($index);

    /**
     * Removes a session variable from an application context
     */
    public function remove($index);

    /**
     * Returns active session id
     */
    public function getId();

    /**
     * Check whether the session has been started
     */
    public function isStarted();

    /**
     * Destroys the active session
     */
    public function destroy($removeData = false);

    /**
     * Regenerate session's id
     */
    public function regenerateId($deleteOldSession = true);
}
