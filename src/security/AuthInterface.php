<?php
namespace kuiper\web\security;

interface AuthInterface extends ArrayAccess
{
    /**
     * make current user logged in
     *
     * @param array $identity the user identity info
     */
    public function login($identity);

    /**
     * @return array
     */
    public function getIdentity();

    /**
     * make current user logged out
     *
     * @param bool $destroySession trigger destroy session
     */
    public function logout($destroySession = true);

    /**
     * whether current user is logged in
     *
     * @return boolean
     */
    public function isGuest();

    /**
     * whether current user is required to login
     *
     * @return boolean
     */
    public function isNeedLogin();
}
