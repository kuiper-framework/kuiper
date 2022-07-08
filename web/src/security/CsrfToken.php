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

    /**
     * @var array
     */
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
    public function getTokenKey(): string
    {
        return $this->options['tokenParamKey'];
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): string
    {
        $token = $this->getSessionTokenValue();
        if (null === $token) {
            $token = $this->regenerateToken();
        }

        return $token;
    }

    /**
     * @return string
     */
    public function getSessionTokenValue(): ?string
    {
        $value = $this->session->get($this->options['tokenValueSessionId']);

        return is_string($value) ? $value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkToken(?string $tokenValue, bool $destroyIfValid = true): bool
    {
        $valid = null !== $tokenValue && $this->getSessionTokenValue() === $tokenValue;
        if ($valid && $destroyIfValid) {
            $this->session->remove($this->options['tokenValueSessionId']);
        }

        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function check(ServerRequestInterface $request, $destroyIfValid = true): bool
    {
        $post = $request->getParsedBody();
        $tokenValue = isset($post[$this->getTokenKey()])
            ? $post[$this->getTokenKey()]
            : $request->getHeaderLine('x-csrf-token');

        return $this->checkToken($tokenValue, $destroyIfValid);
    }

    protected function generateRandomString(int $bytes): string
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
    public function regenerateToken(): string
    {
        $token = $this->generateRandomString($this->options['tokenValueLength']);
        $this->session->set($this->options['tokenValueSessionId'], $token);

        return $token;
    }
}
