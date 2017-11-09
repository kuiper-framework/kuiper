<?php

namespace kuiper\web\session;

interface FlashInterface
{
    /**
     * Checks whether there are messages.
     *
     * @param string|null $type
     *
     * @return bool
     */
    public function has(string $type = null);

    /**
     * Shows a HTML error message.
     *
     * @param string $message
     */
    public function error(string $message);

    /**
     * Shows a HTML notice/information message.
     *
     * @param string $message
     */
    public function notice(string $message);

    /**
     * Shows a HTML success message.
     *
     * @param $message
     */
    public function success(string $message);

    /**
     * Shows a HTML warning message.
     *
     * @param string $message
     */
    public function warning(string $message);

    /**
     * Outputs a message.
     *
     * @param string $type
     * @param string $message
     */
    public function message(string $type, string $message);
}
