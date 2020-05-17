<?php

declare(strict_types=1);

namespace kuiper\web\security;

use Psr\Http\Message\ServerRequestInterface;

interface CsrfTokenInterface
{
    /**
     * Gets key to be used as input's name in a CSRF check.
     *
     * @return string
     */
    public function getTokenKey();

    /**
     * Gets token value to be used as input's value in a CSRF check.
     *
     * @return string
     */
    public function getToken();

    /**
     * Regenerate the CSRF token value.
     */
    public function regenerateToken();

    /**
     * Check if the CSRF token is the same that the current in session.
     *
     * @param string $tokenValue
     * @param bool   $destroyIfValid
     *
     * @return bool
     */
    public function checkToken($tokenValue, $destroyIfValid = true);

    /**
     * Check if the CSRF token sent in the request is the same that the current in session.
     *
     * @param bool $destroyIfValid
     *
     * @return bool
     */
    public function check(ServerRequestInterface $request, $destroyIfValid = true);
}
