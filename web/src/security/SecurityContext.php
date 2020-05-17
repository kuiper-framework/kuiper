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

    private static $COMPONENTS = [
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

    public static function fromRequest(ServerRequestInterface $request): self
    {
        $session = $request->getAttribute(self::SESSION);
        if (!$session) {
            throw new \BadMethodCallException('SessionMiddleware not enabled');
        }

        return self::createComponent(SecurityContext::class, $session);
    }

    private static function createComponent(string $component, SessionInterface $session)
    {
        $class = self::$COMPONENTS[$component];

        return new $class($session);
    }

    public static function setComponent(string $component, string $implClass): void
    {
        self::$COMPONENTS[$component] = $implClass;
    }
}
