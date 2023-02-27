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

namespace kuiper\web\http;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class ResponseHelper
{
    public static function parseSetCookieHeader(array $setCookies): array
    {
        $cookies = [];
        foreach ($setCookies as $setCookie) {
            $rawAttributes = self::splitOnAttributeDelimiter($setCookie);

            $rawAttribute = array_shift($rawAttributes);

            if (!is_string($rawAttribute)) {
                throw new InvalidArgumentException(sprintf('The provided cookie string "%s" must have at least one attribute', $setCookie));
            }

            [$cookieName] = self::splitCookiePair($rawAttribute);
            $cookies[$cookieName] = $setCookie;
        }

        return $cookies;
    }

    /** @return string[] */
    public static function splitOnAttributeDelimiter(string $string): array
    {
        $splitAttributes = preg_split('@\s*;\s*@', $string);

        assert(is_array($splitAttributes));

        return array_filter($splitAttributes);
    }

    /** @return string[] */
    public static function splitCookiePair(string $string): array
    {
        $pairParts = explode('=', $string, 2);
        $pairParts[1] = urldecode($pairParts[1] ?? '');

        return $pairParts;
    }

    public static function buildSetCookie(string $cookieName, string $value, array $attributes): string
    {
        $cookieStringParts = [
            urlencode($cookieName).'='.urlencode($value),
        ];
        foreach ($attributes as $name => $attribute) {
            if (empty($attribute)) {
                continue;
            }
            if (is_bool($attribute)) {
                $cookieStringParts[] = $name;
            } else {
                $cookieStringParts[] = $name.'='.$attribute;
            }
        }

        return implode('; ', $cookieStringParts);
    }

    public static function setCookie(ResponseInterface $response, array $cookies): ResponseInterface
    {
        $modified = $response->withoutHeader('set-cookie');
        foreach ($cookies as $cookie) {
            $modified = $modified->withAddedHeader('set-cookie', $cookie);
        }

        return $modified;
    }
}
