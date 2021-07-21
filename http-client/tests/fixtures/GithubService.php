<?php

declare(strict_types=1);

namespace kuiper\http\client\fixtures;

use kuiper\http\client\annotation\GetMapping;
use kuiper\http\client\annotation\RequestHeader;

/**
 * @RequestHeader("content-type: application/json")
 */
interface GithubService
{
    /**
     * @GetMapping("/users/{user}/list")
     *
     * @return GitRepository[]
     */
    public function listRepos(string $user): array;
}
