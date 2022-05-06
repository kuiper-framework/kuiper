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

use kuiper\http\client\attribute\GetMapping;
use kuiper\http\client\attribute\HttpClient;
use kuiper\http\client\attribute\HttpHeader;

#[HttpClient]
#[HttpHeader('content-type', 'application/json')]
interface GithubService
{
    /**
     * @return GitRepository[]
     */
    #[GetMapping("/users/{user}/list")]
    public function listRepos(string $user): array;
}
