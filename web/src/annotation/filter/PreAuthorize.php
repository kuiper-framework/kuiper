<?php

declare(strict_types=1);

namespace kuiper\web\annotation\filter;

use kuiper\web\security\AclInterface;
use kuiper\web\security\SecurityContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class PreAuthorize extends AbstractFilter
{
    /**
     * @var string[]
     */
    public $value;

    public static $SUPERUSER = 'Admin';

    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container): MiddlewareInterface
    {
        return new class($container->get(AclInterface::class), $this->value) implements MiddlewareInterface {
            /**
             * @var AclInterface
             */
            private $acl;

            /**
             * @var string[]
             */
            private $authorities;

            /**
             *  constructor.
             *
             * @param string[] $authorities
             */
            public function __construct(AclInterface $acl, array $authorities)
            {
                $this->acl = $acl;

                foreach ($authorities as $authority) {
                    if (false === strpos($authority, ':')) {
                        throw new \InvalidArgumentException("Acl resource should in format 'resource:action'");
                    }
                    $this->authorities[] = explode(':', $authority, 2);
                }
            }

            public function check(array $roles): bool
            {
                if (empty($roles)) {
                    return false;
                }

                if (in_array(PreAuthorize::$SUPERUSER, $roles, true)) {
                    return true;
                }

                foreach ($this->authorities as $authority) {
                    $allow = false;
                    foreach ($roles as $role) {
                        if ($this->acl->isAllowed($role, ...$authority)) {
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

                if ($auth->isGuest()) {
                    throw new HttpUnauthorizedException($request);
                }
                if (!$this->check($auth['roles'] ?? [])) {
                    throw new HttpForbiddenException($request);
                }

                return $handler->handle($request);
            }
        };
    }
}
