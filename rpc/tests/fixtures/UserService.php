<?php

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
