<?php

declare(strict_types=1);

namespace kuiper\web\session;

use kuiper\web\fixtures\User;
use kuiper\web\security\SecurityContext;
use kuiper\web\TestCase;

class SecurityContextTest extends TestCase
{
    public function testSetIdentity()
    {
        $user = new User();
        SecurityContext::setIdentity($user);
        $this->assertSame($user, SecurityContext::getIdentity());
    }
}
