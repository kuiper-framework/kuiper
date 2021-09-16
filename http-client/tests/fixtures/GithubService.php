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

namespace kuiper\http\client\fixtures;

use kuiper\http\client\annotation\GetMapping;
use kuiper\http\client\annotation\HttpClient;
use kuiper\http\client\annotation\RequestHeader;

/**
 * @HttpClient
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
