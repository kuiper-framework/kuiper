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

use BadMethodCallException;
use GuzzleHttp\Psr7\ServerRequest;
use InvalidArgumentException;
use kuiper\swoole\http\ServerRequestHolder;
use kuiper\web\session\EphemeralSession;
use kuiper\web\session\FlashInterface;
use kuiper\web\session\SessionFlash;
use kuiper\web\session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;

class SecurityContext
{
    public const SESSION = '__session__';

    /**
     * @var string[]
     */
    protected static array $COMPONENTS = [
        SecurityContext::class => SecurityContext::class,
        FlashInterface::class => SessionFlash::class,
        CsrfTokenInterface::class => CsrfToken::class,
        AuthInterface::class => Auth::class,
    ];

    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function getFlash(): FlashInterface
    {
        return self::createComponent(FlashInterface::class, $this->session);
    }

    public function getCsrfToken(): CsrfTokenInterface
    {
        return self::createComponent(CsrfTokenInterface::class, $this->session);
    }

    public function getAuth(): AuthInterface
    {
        return self::createComponent(AuthInterface::class, $this->session);
    }

    public function getSession(): SessionInterface
    {
        return $this->session;
    }

    public static function setIdentity(UserIdentity $userIdentity, ?ServerRequestInterface $request = null): ServerRequestInterface
    {
        if (null === $request) {
            $request = ServerRequestHolder::getRequest();
            if (null === $request) {
                if (!class_exists(ServerRequest::class)) {
                    throw new InvalidArgumentException('guzzlehttp/psr7 is required');
                }
                $request = new ServerRequest('GET', '/');
                ServerRequestHolder::setRequest($request);
            }
        }

        $session = $request->getAttribute(static::SESSION);
        if (null === $session) {
            $session = new EphemeralSession();
            $request = $request->withAttribute(static::SESSION, $session);
            ServerRequestHolder::setRequest($request);
        }
        /** @var AuthInterface $auth */
        $auth = self::createComponent(AuthInterface::class, $session);
        $auth->login($userIdentity);

        return $request;
    }

    public static function getIdentity(?ServerRequestInterface $request = null): ?UserIdentity
    {
        if (null === $request) {
            $request = ServerRequestHolder::getRequest();
            if (null === $request) {
                return null;
            }
        }
        $session = $request->getAttribute(static::SESSION);
        if (null === $session) {
            return null;
        }
        /** @var AuthInterface $auth */
        $auth = self::createComponent(AuthInterface::class, $session);

        return $auth->isAuthorized() ? $auth->getIdentity() : null;
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        $session = $request->getAttribute(static::SESSION);
        if (null === $session) {
            throw new BadMethodCallException('SessionMiddleware not enabled');
        }

        return self::createComponent(__CLASS__, $session);
    }

    private static function createComponent(string $component, SessionInterface $session): mixed
    {
        $class = self::$COMPONENTS[$component];

        return new $class($session);
    }

    public static function setComponent(string $component, string $implClass): void
    {
        static::$COMPONENTS[$component] = $implClass;
    }
}
