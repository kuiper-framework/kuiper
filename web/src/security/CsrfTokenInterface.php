<?php

declare(strict_types=1);

namespace kuiper\web\security;

use Psr\Http\Message\ServerRequestInterface;

interface CsrfTokenInterface
{
    /**
     * Gets key to be used as input's name in a CSRF check.
     */
    public function getTokenKey(): string;

    /**
     * Gets token value to be used as input's value in a CSRF check.
     */
    public function getToken(): string;

    /**
     * Regenerate the CSRF token value.
     */
    public function regenerateToken(): string;

    /**
     * Check if the CSRF token is the same that the current in session.
     */
    public function checkToken(?string $tokenValue, bool $destroyIfValid = true): bool;

    /**
     * Check if the CSRF token sent in the request is the same that the current in session.
     *
     * @param bool $destroyIfValid
     */
    public function check(ServerRequestInterface $request, $destroyIfValid = true): bool;
}
