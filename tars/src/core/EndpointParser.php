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

namespace kuiper\tars\core;

use kuiper\helper\Arrays;
use kuiper\rpc\servicediscovery\ServiceEndpoint;
use kuiper\rpc\ServiceLocator;
use kuiper\rpc\transporter\Endpoint;
use kuiper\tars\integration\EndpointF;

class EndpointParser
{
    /**
     * @var string[]
     */
    private static $SHORT_OPTIONS = [
        'host' => 'h',
        'port' => 'p',
        'timeout' => 't',
        'encrypted' => 'e',
    ];

    public static function parseServiceEndpoint(string $str): ServiceEndpoint
    {
        $pos = strpos($str, '@');
        if (false === $pos) {
            throw new \InvalidArgumentException("No servant name in '$str'");
        }
        $servantName = substr($str, 0, $pos);
        $endpointList = substr($str, $pos + 1);

        $endpoints = [];
        foreach (explode(':', $endpointList) as $endpointStr) {
            $endpoint = self::parse($endpointStr);
            $endpoints[] = $endpoint;
        }

        return new ServiceEndpoint(new ServiceLocator($servantName), $endpoints);
    }

    public static function fromEndpointF(EndpointF $endpointF): Endpoint
    {
        $timeout = $endpointF->timeout > 0 ? $endpointF->timeout / 1000 : null;

        return new Endpoint(
            $endpointF->istcp > 0 ? 'tcp' : 'udp',
            $endpointF->host,
            $endpointF->port,
            $timeout,
            $timeout,
            ['weight' => $endpointF->weight]
        );
    }

    public static function parse(string $str): Endpoint
    {
        $address = [
            'protocol' => '',
            'host' => '',
            'port' => 0,
            'timeout' => 0,
            'weight' => 100,
            'encrypted' => false,
        ];
        $parts = preg_split("/\s+/", $str);
        $address['protocol'] = array_shift($parts);
        while (!empty($parts)) {
            $opt = array_shift($parts);
            if (0 === strpos($opt, '-')) {
                $name = array_search(substr($opt, 1), self::$SHORT_OPTIONS, true);
                if (false === $name) {
                    continue;
                }
                if ('encrypted' === $name) {
                    $address[$name] = true;
                    continue;
                }
                $value = array_shift($parts);
                if (in_array($name, ['port', 'timeout'], true)) {
                    $address[$name] = (int) $value;
                } else {
                    $address[$name] = $value;
                }
            }
        }

        if (!in_array($address['protocol'], ['tcp', 'udp'], true)) {
            throw new \InvalidArgumentException("invalid address protocol: original text is '$str'");
        }
        if (empty($address['host'])) {
            throw new \InvalidArgumentException("invalid address host: original text is '$str'");
        }
        if ($address['port'] < 1 || $address['port'] > 65536) {
            throw new \InvalidArgumentException("invalid address port: original text is '$str'");
        }

        $timeout = $address['timeout'] > 0 ? $address['timeout'] / 1000 : null;

        return new Endpoint(
            $address['protocol'],
            $address['host'],
            $address['port'],
            $timeout,
            $timeout,
            Arrays::exclude($address, ['protocol', 'host', 'port', 'timeout'])
        );
    }
}
