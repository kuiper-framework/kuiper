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

namespace kuiper\web\handler;

use Psr\Http\Message\ServerRequestInterface;

class DefaultLoginUrlBuilder implements LoginUrlBuilderInterface
{
    public function __construct(
        private readonly string $loginUrl = '/login',
        private readonly ?string $redirectParam = 'redirect')
    {
    }

    public function build(ServerRequestInterface $request): string
    {
        if (isset($this->redirectParam)) {
            $redirectUrl = (string) $request->getUri();

            return $this->loginUrl
                .(str_contains($this->loginUrl, '?') ? '&' : '?')
                .$this->redirectParam.'='.urlencode($redirectUrl);
        } else {
            return $this->loginUrl;
        }
    }
}
