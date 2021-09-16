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

namespace kuiper\rpc\fixtures;

interface UserService
{
    public function findUser(int $id): ?User;

    /**
     * @return User[]
     */
    public function findAllUser(?int &$total): array;

    public function saveUser(User $user): void;
}
