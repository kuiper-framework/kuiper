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

use Psr\Http\Message\ServerRequestInterface;

class CacheStoreSessionFactory implements SessionFactoryInterface
{
    /**
     * @var int
     */
    private $cookieLifetime = 1800;

    /**
     * @var string
     */
    private $cookieName = 'PHPSESSIONID';

    /**
     * @var \SessionHandlerInterface
     */
    private $sessionHandler;

    /**
     * @var bool
     */
    private $compatibleMode;
    /**
     * @var bool
     */
    private $autoStart;

    public function __construct(\SessionHandlerInterface $handler, array $options = [])
    {
        if (isset($options['cookie_lifetime'])) {
            $this->cookieLifetime = (int) $options['cookie_lifetime'];
        }
        if (isset($options['cookie_name'])) {
            $this->cookieName = $options['cookie_name'];
        }
        $this->compatibleMode = !empty($options['compatible_mode']);
        if ($this->compatibleMode) {
            session_start(); // for session_encode/session_decode
        }
        $this->autoStart = !empty($options['auto_start']);
        $this->sessionHandler = $handler;
    }

    public function create(ServerRequestInterface $request): SessionInterface
    {
        return new CacheStoreSession($this->sessionHandler, $request, $this->cookieName,
            $this->cookieLifetime, $this->compatibleMode, $this->autoStart);
    }
}
