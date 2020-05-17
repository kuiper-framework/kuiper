<?php

declare(strict_types=1);

namespace kuiper\web\session;

interface FlashInterface
{
    /**
     * Checks whether there are messages.
     */
    public function has(string $type = null): bool;

    /**
     * Shows a HTML error message.
     */
    public function error(string $message): void;

    /**
     * Shows a HTML notice/information message.
     */
    public function notice(string $message): void;

    /**
     * Shows a HTML success message.
     */
    public function success(string $message): void;

    /**
     * Shows a HTML warning message.
     */
    public function warning(string $message): void;

    /**
     * Outputs a message.
     */
    public function message(string $type, string $message): void;

    /**
     * Clears all messages.
     */
    public function clearAll(): void;

    /**
     * Gets all messages.
     */
    public function getMessages(): array;
}
