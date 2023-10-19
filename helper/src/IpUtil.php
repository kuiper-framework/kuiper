<?php

declare(strict_types=1);

namespace kuiper\helper;

class IpUtil
{
    public static function cidrMatch(string $ip, string|array $range): bool
    {
        if (!is_array($range)) {
            $range = explode(',', $range);
        }
        $ip = ip2long($ip);
        if (false === $ip) {
            return false;
        }
        foreach ($range as $ipRange) {
            if (str_contains($ipRange, '/')) {
                [$subnet, $bits] = explode('/', $ipRange, 2);
            } else {
                $subnet = $ipRange;
                $bits = 32;
            }
            $subnet = ip2long($subnet);
            if (false === $subnet) {
                continue;
            }
            $mask = -1 << (32 - $bits);
            $subnet &= $mask; // nb: in case the supplied subnet wasn't correctly aligned

            if (($ip & $mask) === $subnet) {
                return true;
            }
        }

        return false;
    }
}
