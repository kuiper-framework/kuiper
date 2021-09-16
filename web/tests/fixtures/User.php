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

namespace kuiper\web\fixtures;

use kuiper\web\security\UserIdentity;

class User implements UserIdentity
{
    /**
     * @var string
     */
    private $username;
    /**
     * @var string[]
     */
    private $authorities;

    public function __construct(string $username, array $authorities)
    {
        $this->username = $username;
        $this->authorities = $authorities;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getAuthorities(): array
    {
        return $this->authorities;
    }
}
