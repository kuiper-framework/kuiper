<?php
namespace kuiper\web\security;

use Psr\Http\Message\ServerRequestInterface;

interface CsrfTokenInterface
{
    /**
     * Generates a pseudo random token key to be used as input's name in a CSRF check
     * 
     * @param int $numberBytes
     * @return string
     */
    public function getTokenKey($numberBytes = null);

    /**
     * Generates a pseudo random token value to be used as input's value in a CSRF check
     * 
     * @param int $numberBytes
     * @return string
     */
    public function getToken($numberBytes = null);

    /**
     * Check if the CSRF token is the same that the current in session
     * 
     * @param string $tokenValue
     * @param boolean $destroyIfValid
     * @return boolean
     */
    public function checkToken($tokenValue, $destroyIfValid = true);

    /**
     * Check if the CSRF token sent in the request is the same that the current in session
     * 
     * @param ServerRequestInterface $request
     * @param boolean $destroyIfValid
     * @return boolean
     */
    public function check(ServerRequestInterface $request, $destroyIfValid = true);
}