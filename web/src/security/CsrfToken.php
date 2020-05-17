<?php

declare(strict_types=1);

namespace kuiper\web\security;

use kuiper\web\session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;

class CsrfToken implements CsrfTokenInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var array
     */
    private $options;

    private static $DEFAULT_OPTIONS = [
        'tokenParamKey' => '_token',
        'tokenValueSessionId' => 'csrf:val',
        'tokenValueLength' => 32,
    ];

    public function __construct(SessionInterface $session, array $options = [])
    {
        $this->session = $session;
        $this->options = array_merge(self::$DEFAULT_OPTIONS, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenKey()
    {
        return $this->options['tokenParamKey'];
    }

    /**
     * {@inheritdoc}
     */
    public function getToken()
    {
        $token = $this->getSessionTokenValue();
        if (!$token) {
            $token = $this->regenerateToken();
        }

        return $token;
    }

    /**
     * @return string
     */
    public function getSessionTokenValue()
    {
        return $this->session->get($this->options['tokenValueSessionId']);
    }

    /**
     * {@inheritdoc}
     */
    public function checkToken($tokenValue, $destroyIfValid = true)
    {
        $valid = $this->getSessionTokenValue() == $tokenValue;
        if ($valid && $destroyIfValid) {
            $this->session->remove($this->options['tokenValueSessionId']);
        }

        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function check(ServerRequestInterface $request, $destroyIfValid = true)
    {
        $post = $request->getParsedBody();
        $tokenValue = isset($post[$this->getTokenKey()])
            ? $post[$this->getTokenKey()]
            : $request->getHeaderLine('x-csrf-token');

        return $tokenValue && $this->checkToken($tokenValue, $destroyIfValid);
    }

    protected function generateRandomString($bytes)
    {
        if (!function_exists('openssl_random_pseudo_bytes')) {
            throw new \RuntimeException('openssl extension must be loaded');
        }
        $string = base64_encode(openssl_random_pseudo_bytes($bytes));

        return preg_replace('/[^0-9a-zA-Z]/', '', base64_encode($string));
    }

    /**
     * Regenerate the CSRF token value.
     */
    public function regenerateToken()
    {
        $token = $this->generateRandomString($this->options['tokenValueLength']);
        $this->session->set($this->options['tokenValueSessionId'], $token);

        return $token;
    }
}
