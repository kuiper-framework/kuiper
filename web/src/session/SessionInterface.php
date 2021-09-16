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
    public function set($index, $value): void;

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
     */
    public function destroy(bool $removeData = false): bool;

    /**
     * Regenerate session's id.
     */
    public function regenerateId(bool $deleteOldSession = true): void;

    public function setCookie(ResponseInterface $response): ResponseInterface;
}
