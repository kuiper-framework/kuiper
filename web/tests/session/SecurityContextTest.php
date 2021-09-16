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
