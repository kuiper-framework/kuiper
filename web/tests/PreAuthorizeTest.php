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

namespace kuiper\web;

use kuiper\web\fixtures\User;
use kuiper\web\security\SecurityContext;
use kuiper\web\session\EphemeralSession;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;

class PreAuthorizeTest extends TestCase
{
    public function testAnyPermissionHasOne()
    {
        $request = $this->createRequest('GET /auth/index')
        ->withAttribute(SecurityContext::SESSION, new EphemeralSession());
        SecurityContext::setIdentity(new User('u', ['book:view']), $request);
        $response = $this->getContainer()->get(RequestHandlerInterface::class)->handle($request);
        $this->assertTrue(true);
    }

    public function testAnyPermissionHasNone()
    {
        $this->expectException(HttpForbiddenException::class);
        $request = $this->createRequest('GET /auth/index')
            ->withAttribute(SecurityContext::SESSION, new EphemeralSession());
        SecurityContext::setIdentity(new User('u', ['blog:edit']), $request);
        $response = $this->getContainer()->get(RequestHandlerInterface::class)->handle($request);
        $this->assertTrue(true);
    }

    public function testAllPermissionAny()
    {
        $request = $this->createRequest('GET /auth/home')
            ->withAttribute(SecurityContext::SESSION, new EphemeralSession());
        SecurityContext::setIdentity(new User('u', ['book:*']), $request);
        $response = $this->getContainer()->get(RequestHandlerInterface::class)->handle($request);
        $this->assertTrue(true);
    }

    public function testAllPermissionSuperadmin()
    {
        $request = $this->createRequest('GET /auth/home')
            ->withAttribute(SecurityContext::SESSION, new EphemeralSession());
        SecurityContext::setIdentity(new User('u', ['admin']), $request);
        $response = $this->getContainer()->get(RequestHandlerInterface::class)->handle($request);
        $this->assertTrue(true);
    }

    public function testAllPermissionAll()
    {
        $request = $this->createRequest('GET /auth/home')
            ->withAttribute(SecurityContext::SESSION, new EphemeralSession());
        SecurityContext::setIdentity(new User('u', ['book:edit', 'book:view']), $request);
        $response = $this->getContainer()->get(RequestHandlerInterface::class)->handle($request);
        $this->assertTrue(true);
    }

    public function testNoPermission()
    {
        $this->expectException(HttpForbiddenException::class);
        $request = $this->createRequest('GET /auth/index')
            ->withAttribute(SecurityContext::SESSION, new EphemeralSession());
        SecurityContext::setIdentity(new User('u', []), $request);
        $response = $this->getContainer()->get(RequestHandlerInterface::class)->handle($request);
    }
}
