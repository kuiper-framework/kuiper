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
    private readonly PermissionEvaluator $permissionEvaluator;

    public function __construct(
        AclInterface $acl,
        private readonly array $requiredAuthorities,
        private readonly array $anyAuthorities)
    {
        $this->permissionEvaluator = new PermissionEvaluator($acl);
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
