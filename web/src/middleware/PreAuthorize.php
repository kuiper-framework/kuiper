<?php

declare(strict_types=1);

namespace kuiper\web\middleware;

use kuiper\web\security\AclInterface;
use kuiper\web\security\SecurityContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class PreAuthorize implements MiddlewareInterface
{
    /**
     * @var AclInterface
     */
    private $acl;

    /**
     * @var string[]
     */
    private $authorities;

    /**
     * @var string
     */
    private static $SUPER_USER_ROLE = 'admin';

    /**
     *  constructor.
     *
     * @param string[] $authorities
     */
    public function __construct(AclInterface $acl, array $authorities)
    {
        $this->acl = $acl;
        $this->authorities = $authorities;
    }

    public function isAllowed(array $roles): bool
    {
        if (empty($roles)) {
            return false;
        }

        if (empty($this->authorities) || $this->isSuperUser($roles)) {
            return true;
        }

        foreach ($this->authorities as $authority) {
            $allow = false;
            foreach ($roles as $role) {
                if ($this->acl->isAllowed($role, $authority)) {
                    $allow = true;
                    break;
                }
            }
            if (!$allow) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = SecurityContext::fromRequest($request)->getAuth();

        if (!$auth->isAuthorized()) {
            throw new HttpUnauthorizedException($request);
        }
        if (!$this->isAllowed($auth->getIdentity()->getAuthorities())) {
            throw new HttpForbiddenException($request);
        }

        return $handler->handle($request);
    }

    /**
     * The super user role name.
     */
    public static function setSuperUserRole(string $roleName): void
    {
        self::$SUPER_USER_ROLE = $roleName;
    }

    protected function isSuperUser(array $roles): bool
    {
        return in_array(self::$SUPER_USER_ROLE, $roles, true);
    }
}
