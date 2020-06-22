<?php

declare(strict_types=1);

namespace kuiper\web\security;

use kuiper\web\session\FlashInterface;
use kuiper\web\session\SessionFlash;
use kuiper\web\session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;

class SecurityContext
{
    public const SESSION = '__session__';

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

    public static function fromRequest(ServerRequestInterface $request): self
    {
        $session = $request->getAttribute(static::SESSION);
        if (!$session) {
            throw new \BadMethodCallException('SessionMiddleware not enabled');
        }

        return static::createComponent(SecurityContext::class, $session);
    }

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
