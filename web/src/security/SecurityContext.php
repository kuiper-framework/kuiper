<?php

declare(strict_types=1);

namespace kuiper\web\security;

use kuiper\swoole\http\ServerRequestHolder;
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
    protected static $COMPONENTS = [
        SecurityContext::class => SecurityContext::class,
        FlashInterface::class => SessionFlash::class,
        CsrfTokenInterface::class => CsrfToken::class,
        AuthInterface::class => Auth::class,
    ];

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * SecurityContext constructor.
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function getFlash(): FlashInterface
    {
        return static::createComponent(FlashInterface::class, $this->session);
    }

    public function getCsrfToken(): CsrfTokenInterface
    {
        return static::createComponent(CsrfTokenInterface::class, $this->session);
    }

    public function getAuth(): AuthInterface
    {
        return static::createComponent(AuthInterface::class, $this->session);
    }

    public function getSession(): SessionInterface
    {
        return $this->session;
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
            throw new \BadMethodCallException('SessionMiddleware not enabled');
        }

        return static::createComponent(SecurityContext::class, $session);
    }

    /**
     * @return mixed
     */
    private static function createComponent(string $component, SessionInterface $session)
    {
        $class = static::$COMPONENTS[$component];

        return new $class($session);
    }

    public static function setComponent(string $component, string $implClass): void
    {
        static::$COMPONENTS[$component] = $implClass;
    }
}
