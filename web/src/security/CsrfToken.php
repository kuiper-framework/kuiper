<?php

namespace kuiper\web\security;

use kuiper\web\session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

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
        'tokenKeySessionId' => 'csrf:key',
        'tokenValueSessionId' => 'csrf:val',
    ];

    public function __construct(SessionInterface $session, array $options = [])
    {
        $this->session = $session;
        $this->options = array_merge(self::$DEFAULT_OPTIONS, $options);
    }

    /**
     * @param int $numberBytes
     *
     * @return string
     */
    public function getTokenKey($numberBytes = null)
    {
        $token = $this->generateRandomString($numberBytes ?: 12);
        $this->session->set($this->options['tokenKeySessionId'], $token);

        return $token;
    }

    /**
     * @param int $numberBytes
     *
     * @return string
     */
    public function getToken($numberBytes = null)
    {
        $token = $this->generateRandomString($numberBytes ?: 12);
        $this->session->set($this->options['tokenValueSessionId'], $token);

        return $token;
    }

    /**
     * @return string
     */
    public function getSessionTokenKey()
    {
        return $this->session->get($this->options['tokenKeySessionId']);
    }

    /**
     * {@inheritdoc}
     */
    public function checkToken($tokenValue, $destroyIfValid = true)
    {
        $valid = $this->session->get($this->options['tokenValueSessionId'])
               == $tokenValue;
        if ($valid && $destroyIfValid) {
            $this->session->remove($this->options['tokenKeySessionId']);
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
        $tokenKey = $this->getSessionTokenKey();

        return $tokenKey
            && isset($post[$tokenKey])
            && $this->checkToken($post[$tokenKey], $destroyIfValid);
    }

    protected function generateRandomString($bytes)
    {
        if (!function_exists('openssl_random_pseudo_bytes')) {
            throw new RuntimeException('openssl extension must be loaded');
        }
        $string = base64_encode(openssl_random_pseudo_bytes($bytes));

        return preg_replace('/[^0-9a-zA-Z]/', '', base64_encode($string));
    }
}
