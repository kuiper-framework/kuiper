<?php

declare(strict_types=1);

namespace kuiper\web\middleware;

use kuiper\web\security\AclInterface;
use kuiper\web\security\PermissionEvaluator;
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
     * @var PermissionEvaluator
     */
    private $permissionEvaluator;

    /**
     * @var array
     */
    private $requiredAuthorities;
    /**
     * @var array
     */
    private $anyAuthorities;

    public function __construct(AclInterface $acl, array $requiredAuthorities, array $anyAuthorities)
    {
        $this->permissionEvaluator = new PermissionEvaluator($acl);
        $this->requiredAuthorities = $requiredAuthorities;
        $this->anyAuthorities = $anyAuthorities;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = SecurityContext::getIdentity($request);

        if (null === $user) {
            throw new HttpUnauthorizedException($request);
        }
        if (!empty($this->requiredAuthorities)
            && !$this->permissionEvaluator->hasPermission($user, $this->requiredAuthorities)) {
            throw new HttpForbiddenException($request);
        }
        if (!empty($this->anyAuthorities)
            && !$this->permissionEvaluator->hasAnyPermission($user, $this->anyAuthorities)) {
            throw new HttpForbiddenException($request);
        }

        return $handler->handle($request);
    }
}
