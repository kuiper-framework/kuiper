<?php

declare(strict_types=1);

namespace kuiper\http\client\fixtures;

use kuiper\http\client\annotation\GetMapping;

interface GithubService
{
    /**
     * @GetMapping("/users/{user}/list")
     *
     * @return GitRepository[]
     */
    public function listRepos(string $user): array;
}
